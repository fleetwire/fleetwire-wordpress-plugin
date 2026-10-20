<?php
namespace Fleetwire {
    class Config {
        public static function get_company_id() { return 'public-company-id'; }
    }
}
namespace {
    $repo = $argv[1];
    $fixture = $argv[2];
    $scenario = $argv[3];
    define( 'ABSPATH', $fixture . '/public/' );
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
    define( 'WP_DEBUG', $scenario === 'debug' );
    $_SERVER['DOCUMENT_ROOT'] = ABSPATH;
    $_SERVER['REMOTE_ADDR'] = '8.8.8.8';
    mkdir( WP_CONTENT_DIR . '/uploads', 0777, true );
    ini_set( 'error_log', $fixture . '/host.log' );
    function wp_upload_dir() { return [ 'basedir' => WP_CONTENT_DIR . '/uploads' ]; }
    function wp_mkdir_p( $path ) { return is_dir( $path ) || @mkdir( $path, 0777, true ); }
    function get_current_blog_id() { return 7; }
    function current_time( $format ) { return '2026-10-08 12:00:00'; }
    function wp_json_encode( $value ) { return json_encode( $value ); }
    function get_current_user_id() { return 42; }
    function wp_unslash( $value ) { return is_string( $value ) ? stripslashes( $value ) : $value; }
    function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
    function check( $condition, $message ) {
        if ( ! $condition ) { throw new \RuntimeException( $message ); }
    }
    $legacy = WP_CONTENT_DIR . '/uploads/fleetwire-logs/fleetwire.log';
    mkdir( dirname( $legacy ), 0777, true );
    file_put_contents( $legacy, "old administrator record\n" );
    if ( $scenario === 'unsafe' ) {
        define( 'FLEETWIRE_LOG_DIR', dirname( $legacy ) );
    } elseif ( $scenario === 'symlink' ) {
        symlink( dirname( $legacy ), $fixture . '/linked-logs' );
        define( 'FLEETWIRE_LOG_DIR', $fixture . '/linked-logs' );
    } elseif ( $scenario === 'file-symlink' ) {
        mkdir( $fixture . '/private', 0700 );
        file_put_contents( ABSPATH . 'untouched.txt', 'unchanged' );
        symlink( ABSPATH . 'untouched.txt', $fixture . '/private/fleetwire.log' );
        define( 'FLEETWIRE_LOG_DIR', $fixture . '/private' );
    } elseif ( $scenario === 'custom' ) {
        define( 'FLEETWIRE_LOG_DIR', $fixture . '/private' );
    } elseif ( $scenario === 'unavailable' ) {
        file_put_contents( $fixture . '/not-directory', 'x' );
        define( 'FLEETWIRE_LOG_DIR', $fixture . '/not-directory/child' );
    }
    require $repo . '/includes/Logger.php';
    \Fleetwire\Logger::init();
    if ( in_array( $scenario, [ 'unsafe', 'symlink', 'file-symlink', 'unavailable' ], true ) ) {
        check( \Fleetwire\Logger::get_log_file() === '', 'Unsafe/unavailable file storage must be refused' );
        \Fleetwire\Logger::error( 'fallback diagnostic', [ 'detail' => 'preserved' ] );
        $host = file_get_contents( $fixture . '/host.log' );
        check( strpos( $host, 'fallback diagnostic' ) !== false && strpos( $host, 'preserved' ) !== false, 'Host fallback must preserve diagnostics' );
        check( file_get_contents( $legacy ) === "old administrator record\n", 'Failed migration must not silently discard old records' );
        if ( $scenario === 'file-symlink' ) {
            check( file_get_contents( ABSPATH . 'untouched.txt' ) === 'unchanged', 'Never follow a private-log symlink' );
        }
        echo json_encode( [ 'scenario' => $scenario, 'passed' => true ] );
        exit;
    }
    $path = \Fleetwire\Logger::get_log_file();
    check( strpos( $path, realpath( ABSPATH ) . '/' ) !== 0, 'Log must be outside web root' );
    check( ! file_exists( $legacy ) || filesize( $legacy ) === 0, 'Legacy public records must be removed' );
    check( file_get_contents( $path ) === "old administrator record\n", 'Legacy record must survive privately' );
    \Fleetwire\Logger::init();
    check( substr_count( file_get_contents( $path ), 'old administrator record' ) === 1, 'Migration must be idempotent' );
    \Fleetwire\Logger::log_ajax_error( 'test', 'admin failure' );
    \Fleetwire\Logger::log_api_error( 'company', 'transport failure' );
    $data = file_get_contents( $path );
    check( strpos( $data, '"user_id":42' ) !== false && strpos( $data, '8.8.8.8' ) !== false, 'Error context must remain intact' );
    check( strpos( $data, 'transport failure' ) !== false, 'API errors must remain intact' );
    \Fleetwire\Logger::info( 'informational control' );
    check( ( strpos( file_get_contents( $path ), 'informational control' ) !== false ) === WP_DEBUG, 'Production/debug level filtering must remain intact' );
    check( \Fleetwire\Logger::get_log_size() > 0, 'Size helper must still report log bytes' );
    check( \Fleetwire\Logger::clear_log(), 'Clear helper must succeed' );
    \Fleetwire\Logger::error( '<?php malicious(); ?> */', [ 'nested' => [ 'ok' => true ] ] );
    check( strpos( file_get_contents( $path ), '<?php malicious(); ?> */' ) !== false, 'Hostile text must remain inert data in private storage' );
    if ( DIRECTORY_SEPARATOR !== '\\' ) {
        clearstatcache();
        check( ( fileperms( dirname( $path ) ) & 0777 ) === 0700, 'Private directory permissions' );
        check( ( fileperms( $path ) & 0777 ) === 0600, 'Private file permissions' );
    }
    echo json_encode( [ 'scenario' => $scenario, 'passed' => true, 'logPath' => $path, 'publicRoot' => ABSPATH ] );
}
