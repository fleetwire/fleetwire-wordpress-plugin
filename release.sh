#!/usr/bin/env bash

# License: GPL v3

# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>

# WARNING: Never echo or print secret variables (e.g., GITHUB_ACCESS_TOKEN, SVN_PASSWORD) in logs or output.
# If you must display a secret for debugging, always mask it (e.g., show only the first/last 2 characters).
#
# Example (do not use in production):
# echo "GITHUB_ACCESS_TOKEN: ${GITHUB_ACCESS_TOKEN:0:2}****${GITHUB_ACCESS_TOKEN: -2}"
#
# Review all echo/read statements below to ensure no secrets are ever displayed.

# Strict error handling
set -euo pipefail

# Parse --dry-run flag
DRY_RUN=0
if [ "${1-}" = "--dry-run" ]; then
  DRY_RUN=1
  echo "[DRY RUN MODE ENABLED] No changes will be made."
fi

# Usage/help message
if [ "${1-}" = "--help" ] || [ "${1-}" = "-h" ]; then
  echo "\nUsage: ./release.sh\n"
  echo "This script releases a WordPress plugin from GitHub to WordPress.org SVN."
  echo "\nRequirements:"
  echo "  - .env file with required variables (see script comments)"
  echo "  - git, svn (1.9+), curl, grep, sed, xargs installed"
  echo "\nFlags:"
  echo "  --help, -h   Show this help message and exit."
  exit 0
fi

# Check for required tools
for cmd in git svn curl grep sed xargs; do
  if ! command -v "$cmd" >/dev/null 2>&1; then
    echo "Error: $cmd is not installed." >&2
    exit 1
  fi
done

# Check for .env file
if [ ! -f .env ]; then
  echo "Error: .env file not found. Please create one with the required variables." >&2
  exit 1
fi

read_var() {
    VAR=$(grep $1 $2 | xargs)
    IFS="=" read -ra VAR <<< "$VAR"
    echo ${VAR[1]}
}

set -a
source .env
set +a

# Check for required variables in .env
REQUIRED_VARS=(GITHUB_ACCESS_TOKEN PLUGIN_SLUG GITHUB_REPO_OWNER GITHUB_REPO_NAME SVN_USERNAME SVN_PASSWORD)
for var in "${REQUIRED_VARS[@]}"; do
  if [ -z "${!var-}" ]; then
    echo "Error: Required variable $var is not set in .env or is empty." >&2
    exit 1
  fi
done

# ----- ADD THESE TO A '.env' FILE -----

# THE GITHUB ACCESS TOKEN, GENERATE ONE AT: https://github.com/settings/tokens
GITHUB_ACCESS_TOKEN=$(read_var GITHUB_ACCESS_TOKEN .env)

# The slug of your WordPress.org plugin
PLUGIN_SLUG=$(read_var PLUGIN_SLUG .env)

# GITHUB user who owns the repo
GITHUB_REPO_OWNER=$(read_var GITHUB_REPO_OWNER .env)

# GITHUB Repository name
GITHUB_REPO_NAME=$(read_var GITHUB_REPO_NAME .env)

# SVN user who owns the wordpress repo
SVN_USERNAME=$(read_var SVN_USERNAME .env)

# SVN user password
SVN_PASSWORD=$(read_var SVN_PASSWORD .env)

# ----- STOP HERE -----

set -e
clear

# ASK INFO
echo "--------------------------------------------"
echo "      GITHUB to WordPress.org RELEASER      "
echo "--------------------------------------------"
read -p "TAG AND RELEASE VERSION: " VERSION
echo "--------------------------------------------"
echo ""
echo "PLUGIN_SLUG: ""${PLUGIN_SLUG}"
echo "PLUGIN_SLUG: ""${GITHUB_REPO_OWNER}"
echo "PLUGIN_SLUG: ""${GITHUB_REPO_NAME}"
echo "Before continuing, confirm that you have done the following :)"
echo ""
read -p " - Added a changelog for "${VERSION}"?"
read -p " - Set version in the readme.txt and main file to "${VERSION}"?"
read -p " - Set stable tag in the readme.txt file to "${VERSION}"?"
read -p " - Updated the POT file?"
read -p " - Committed all changes up to GITHUB?"
echo ""
read -p "PRESS [ENTER] TO BEGIN RELEASING "${VERSION}
clear

# VARS
ROOT_PATH=$(pwd)"/"
TEMP_GITHUB_REPO=${PLUGIN_SLUG}"-git"
TEMP_SVN_REPO=${PLUGIN_SLUG}"-svn"
SVN_REPO="https://plugins.svn.wordpress.org/${PLUGIN_SLUG}/"
GIT_PUBLIC_REPO="https://github.com/${GITHUB_REPO_OWNER}/${GITHUB_REPO_NAME}.git"

# Preview exits before changing files or contacting either publication service.
if [ "$DRY_RUN" -eq 1 ]; then
  echo "DRY RUN: Would clone $GIT_PUBLIC_REPO"
  echo "DRY RUN: Would publish to $SVN_REPO"
  exit 0
fi

GIT_REPO="https://${GITHUB_ACCESS_TOKEN}@github.com/${GITHUB_REPO_OWNER}/${GITHUB_REPO_NAME}.git"

# Reused and switched working-copy paths must retain the expected HTTPS authority.
assert_svn_target() {
  local expected="${SVN_REPO%/}" root_url entries entry url
  root_url=$(svn info --show-item url "$1") || {
    echo "Error: Unable to inspect SVN working copy." >&2
    exit 1
  }
  if [ "${root_url%/}" != "$expected" ]; then
    echo "Error: SVN working copy must use the expected HTTPS repository." >&2
    exit 1
  fi
  entries=$(svn info --recursive --show-item url "$1") || {
    echo "Error: Unable to inspect SVN working-copy descendants." >&2
    exit 1
  }
  if [ -z "$entries" ]; then
    echo "Error: SVN working-copy inspection returned no URLs." >&2
    exit 1
  fi
  while IFS= read -r entry; do
    # Recursive --show-item output appends a local path after the encoded URL.
    url=${entry%% *}
    case "$url" in
      "$expected"|"$expected"/*) ;;
      *) echo "Error: SVN working-copy descendant uses an unexpected repository." >&2; exit 1 ;;
    esac
  done <<< "$entries"
}


# DELETE OLD TEMP DIRS
rm -Rf $ROOT_PATH$TEMP_GITHUB_REPO

# CHECKOUT SVN DIR IF NOT EXISTS
if [[ ! -d $TEMP_SVN_REPO ]];
then
    echo "Checking out WordPress.org plugin repository"
    svn checkout --ignore-externals "$SVN_REPO" "$TEMP_SVN_REPO" || { echo "Unable to checkout repo."; exit 1; }
fi
assert_svn_target "$ROOT_PATH$TEMP_SVN_REPO"

# CLONE GIT DIR
echo "Cloning GIT repository from GITHUB: https://github.com/${GITHUB_REPO_OWNER}/${GITHUB_REPO_NAME}.git"
git clone --progress "$GIT_REPO" "$TEMP_GITHUB_REPO" || { echo "Unable to clone repo."; exit 1; }

# MOVE INTO GIT DIR
cd $ROOT_PATH$TEMP_GITHUB_REPO

# LIST BRANCHES
clear
git fetch origin
echo "WHICH BRANCH DO YOU WISH TO DEPLOY? [default: main]"
git branch -r | less -FRX || { echo "Unable to list branches."; exit 1; }
echo ""
read -p "origin/ (press Enter for 'main'): " BRANCH
BRANCH=${BRANCH:-main}

# Switch Branch
echo "Switching to branch"
git checkout ${BRANCH} || { echo "Unable to checkout branch."; exit 1; }

echo ""
read -p "PRESS [ENTER] TO DEPLOY BRANCH "${BRANCH}

# REMOVE UNWANTED FILES & FOLDERS
echo "Removing unwanted files"
rm -Rf .git
rm -Rf .github
rm -Rf .wordpress-org
rm -Rf tests
rm -Rf docs
rm -Rf apigen
rm -f .gitattributes
rm -f .gitignore
rm -f .gitmodules
rm -f .travis.yml
rm -f Gruntfile.js
rm -f package.json
rm -f .jscrsrc
rm -f .jshintrc
rm -f .stylelintrc
rm -f composer.json
rm -f composer.lock
rm -f phpcs.xml
rm -f phpunit.xml
rm -f phpunit.xml.dist
rm -f README.md
rm -f .coveralls.yml
rm -f .editorconfig
rm -f .scrutinizer.yml
rm -f apigen.neon
rm -f CHANGELOG.txt
rm -f CONTRIBUTING.md
rm -f CODE_OF_CONDUCT.md

# MOVE INTO SVN DIR
cd $ROOT_PATH$TEMP_SVN_REPO

# UPDATE SVN
echo "Updating SVN"
assert_svn_target "."
svn update --ignore-externals || { echo "Unable to update SVN."; exit 1; }

# DELETE TRUNK
echo "Replacing trunk"
rm -Rf trunk/

# COPY GIT DIR TO TRUNK
cp -R $ROOT_PATH$TEMP_GITHUB_REPO trunk/

# COPY ASSETS TO SVN ROOT
cp -R $ROOT_PATH$TEMP_GITHUB_REPO/assets .

# DO THE ADD ALL NOT KNOWN FILES UNIX COMMAND
svn add --force * --auto-props --parents --depth infinity -q

# DO THE REMOVE ALL DELETED FILES UNIX COMMAND
MISSING_PATHS=$( svn status | sed -e '/^!/!d' -e 's/^!//' )

# iterate over filepaths
for MISSING_PATH in $MISSING_PATHS; do
    svn rm --force "$MISSING_PATH"
done

# COPY TRUNK TO TAGS/$VERSION
echo "Copying trunk to new tag"
svn copy trunk tags/${VERSION} || { echo "Unable to create tag."; exit 1; }

# DO SVN COMMIT
clear
echo "Showing SVN status"
svn status

# PROMPT USER
echo ""
read -p "PRESS [ENTER] TO COMMIT RELEASE "${VERSION}" TO WORDPRESS.ORG AND GITHUB"
echo ""

# Extract changelog for a given version from readme.txt
extract_changelog() {
  local version="$1"
  local changelog
  changelog=$(awk -v ver="= ${version} =" '
    $0 ~ ver {flag=1; next}
    /^= [0-9]+\.[0-9]+\.[0-9]+ =/ && flag {exit}
    flag {print}
  ' ../readme.txt | sed '/^$/d')
  if [ -z "$changelog" ]; then
    echo "Release of version $version"
  else
    echo "$changelog"
  fi
}

# CREATE THE GITHUB RELEASE
CHANGELOG_BODY=$(extract_changelog "$VERSION")
API_JSON=$(printf '{ "tag_name": "%s","target_commitish": "%s","name": "%s", "body": "%s", "draft": false, "prerelease": false }' "$VERSION" "$BRANCH" "$VERSION" "$CHANGELOG_BODY")
RESULT=$(curl --data "${API_JSON}" https://api.github.com/repos/${GITHUB_REPO_OWNER}/${GITHUB_REPO_NAME}/releases?access_token=${GITHUB_ACCESS_TOKEN})

# DEPLOY
echo ""
echo "Committing to WordPress.org...this may take a while..."
assert_svn_target "."
svn commit -m "Release "${VERSION}", see readme.txt for the changelog." --username "$SVN_USERNAME" --password "$SVN_PASSWORD" || { echo "Unable to commit."; exit 1; }

# REMOVE THE TEMP DIRS
echo "CLEANING UP"
rm -Rf $ROOT_PATH$TEMP_GITHUB_REPO
rm -Rf $ROOT_PATH$TEMP_SVN_REPO

# DONE, BYE
echo "RELEASER DONE :D"
