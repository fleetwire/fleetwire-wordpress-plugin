/**
 * Fleetwire blocks — editor side.
 *
 * Plain JS on the wp.* globals (no build step). Blocks render on the server; the
 * editor shows static previews built from the company's listings, because
 * fleetwire.js only hydrates embeds on the live site.
 */
( function ( wp ) {
    'use strict';

    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var registerBlockType = wp.blocks.registerBlockType;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var components = wp.components;
    var __ = wp.i18n.__;
    var sprintf = wp.i18n.sprintf;
    var apiFetch = wp.apiFetch;
    var settings = window.fleetwireBlocks || { configured: false, settingsUrl: '' };

    /* ---------- Listings (shared by every block, fetched once per editor session) ---------- */

    var listingsRequest = null;
    var listingsSubscribers = [];
    var listingsState = { listings: [], loading: false, error: '' };

    function setListingsState( next ) {
        listingsState = Object.assign( {}, listingsState, next );
        listingsSubscribers.forEach( function ( subscriber ) {
            subscriber( listingsState );
        } );
    }

    function loadListings( refresh ) {
        if ( ! settings.configured ) {
            return;
        }
        if ( listingsRequest && ! refresh ) {
            return;
        }

        setListingsState( { loading: true, error: '' } );
        listingsRequest = apiFetch( { path: '/fleetwire/v1/listings' + ( refresh ? '?refresh=1' : '' ) } )
            .then( function ( listings ) {
                setListingsState( { listings: Array.isArray( listings ) ? listings : [], loading: false } );
            } )
            .catch( function ( error ) {
                setListingsState( { loading: false, error: ( error && error.message ) || __( 'Could not load your vehicles.', 'fleetwire-fleet-management' ) } );
            } );
    }

    function useListings() {
        var state = useState( listingsState );

        useEffect( function () {
            var subscriber = state[ 1 ];
            listingsSubscribers.push( subscriber );
            loadListings( false );
            subscriber( listingsState );

            return function () {
                listingsSubscribers = listingsSubscribers.filter( function ( other ) {
                    return other !== subscriber;
                } );
            };
        }, [] );

        return state[ 0 ];
    }

    /**
     * Stand-in vehicle for inserter previews when the account has no cached listings.
     */
    var SAMPLE_LISTING = {
        id: '',
        name: __( 'Sample vehicle', 'fleetwire-fleet-management' ),
        price: 89,
        price_period: 'day',
        seats: 5,
        doors: 4,
        description: __( 'Your vehicle description appears here: features, pickup details and anything renters should know.', 'fleetwire-fleet-management' ),
        image: '',
        images: [],
    };

    function findListing( listings, id ) {
        for ( var i = 0; i < listings.length; i++ ) {
            if ( listings[ i ].id === id ) {
                return listings[ i ];
            }
        }
        return null;
    }

    function formatPrice( listing ) {
        if ( ! listing || listing.price === null || listing.price === undefined ) {
            return '';
        }
        var amount = Number( listing.price ).toLocaleString( undefined, { maximumFractionDigits: 2 } );
        return '$' + amount + ( listing.price_period ? '/' + listing.price_period : '' );
    }

    /* ---------- Shared UI ---------- */

    function NotConfigured() {
        return el( components.Notice, { status: 'warning', isDismissible: false },
            __( 'Connect your Fleetwire account to show your vehicles.', 'fleetwire-fleet-management' ) + ' ',
            el( 'a', { href: settings.settingsUrl }, __( 'Open Fleetwire settings', 'fleetwire-fleet-management' ) )
        );
    }

    function LiveSiteNote() {
        return el( 'p', { className: 'fleetwire-block-note' },
            __( 'Preview. Live availability, pricing and booking load on your published page.', 'fleetwire-fleet-management' )
        );
    }

    function ListingPicker( props ) {
        var state = props.state;
        var options = state.listings.map( function ( listing ) {
            return { value: listing.id, label: listing.name };
        } );

        return el( Fragment, null,
            el( components.ComboboxControl, {
                label: __( 'Vehicle', 'fleetwire-fleet-management' ),
                value: props.value || null,
                options: options,
                onChange: function ( value ) {
                    props.onChange( value || '' );
                },
                help: state.loading ? __( 'Loading your vehicles…', 'fleetwire-fleet-management' ) : state.error,
                __next40pxDefaultSize: true,
                __nextHasNoMarginBottom: true,
            } ),
            el( components.Button, {
                variant: 'link',
                className: 'fleetwire-block-refresh',
                disabled: state.loading,
                onClick: function () {
                    loadListings( true );
                },
            }, __( 'Refresh vehicle list', 'fleetwire-fleet-management' ) )
        );
    }

    /**
     * Placeholder shown by single-vehicle blocks until a vehicle is picked.
     */
    function PickVehicle( props ) {
        return el( components.Placeholder, {
            icon: props.icon,
            label: props.label,
            instructions: __( 'Choose which vehicle this block shows.', 'fleetwire-fleet-management' ),
            className: 'fleetwire-block-placeholder',
        }, el( 'div', { className: 'fleetwire-block-placeholder__picker' }, el( ListingPicker, props ) ) );
    }

    function Thumb( props ) {
        return props.src
            ? el( 'img', { className: props.className, src: props.src, alt: '' } )
            : el( 'div', { className: props.className + ' is-empty' } );
    }

    function PreviewCard( props ) {
        var listing = props.listing || {};
        return el( 'div', { className: 'fleetwire-preview-card' },
            el( Thumb, { className: 'fleetwire-preview-card__img', src: listing.image } ),
            el( 'div', { className: 'fleetwire-preview-card__body' },
                el( 'strong', null, listing.name || __( 'Your vehicle', 'fleetwire-fleet-management' ) ),
                el( 'div', { className: 'fleetwire-preview-card__footer' },
                    props.showPrice ? el( 'span', { className: 'fleetwire-preview-card__price' }, formatPrice( listing ) || '$—' ) : el( 'span' ),
                    el( 'span', { className: 'fleetwire-preview-button' }, __( 'Book now', 'fleetwire-fleet-management' ) )
                )
            )
        );
    }

    /**
     * Wraps a single-vehicle block: connection notice, picker placeholder, sidebar picker.
     */
    function singleVehicleEdit( options ) {
        return function ( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps();
            var state = useListings();
            var listing = findListing( state.listings, attributes.listingId );
            var pickerProps = {
                state: state,
                value: attributes.listingId,
                onChange: function ( value ) {
                    setAttributes( { listingId: value } );
                },
                icon: options.icon,
                label: options.label,
            };

            // Inserter hover preview (block.json "example"): show the block, not the picker.
            if ( attributes.isPreview ) {
                return el( 'div', blockProps, options.preview( attributes, state.listings[ 0 ] || SAMPLE_LISTING ) );
            }

            var body;
            if ( ! settings.configured ) {
                body = el( NotConfigured );
            } else if ( ! attributes.listingId ) {
                body = el( PickVehicle, pickerProps );
            } else {
                body = el( Fragment, null,
                    options.preview( attributes, listing || { name: attributes.listingId } ),
                    ! listing && ! state.loading && state.listings.length
                        ? el( components.Notice, { status: 'warning', isDismissible: false },
                            __( 'This vehicle is no longer bookable, so it will not show on your site.', 'fleetwire-fleet-management' ) )
                        : null
                );
            }

            return el( Fragment, null,
                el( InspectorControls, null,
                    el( components.PanelBody, { title: __( 'Vehicle', 'fleetwire-fleet-management' ) },
                        settings.configured ? el( ListingPicker, pickerProps ) : el( NotConfigured ),
                        options.controls ? options.controls( attributes, setAttributes ) : null
                    )
                ),
                el( 'div', blockProps, body )
            );
        };
    }

    function toggle( label, checked, onChange ) {
        return el( components.ToggleControl, {
            label: label,
            checked: !! checked,
            onChange: onChange,
            __nextHasNoMarginBottom: true,
        } );
    }

    function range( label, value, min, max, onChange, help ) {
        return el( components.RangeControl, {
            label: label,
            value: value,
            min: min,
            max: max,
            onChange: function ( next ) {
                onChange( next || 0 );
            },
            help: help,
            __next40pxDefaultSize: true,
            __nextHasNoMarginBottom: true,
        } );
    }

    /* Orders the Fleetwire list API accepts; "order" is the host's own arrangement. */
    var SORT_OPTIONS = [
        { label: __( 'Featured (your order)', 'fleetwire-fleet-management' ), value: 'order' },
        { label: __( 'Price: low to high', 'fleetwire-fleet-management' ), value: 'price_asc' },
        { label: __( 'Price: high to low', 'fleetwire-fleet-management' ), value: 'price_desc' },
    ];

    function sortLabel( value ) {
        for ( var i = 0; i < SORT_OPTIONS.length; i++ ) {
            if ( SORT_OPTIONS[ i ].value === value ) {
                return SORT_OPTIONS[ i ].label;
            }
        }

        return SORT_OPTIONS[ 0 ].label;
    }

    /* ---------- Blocks ---------- */

    registerBlockType( 'fleetwire/listings', {
        edit: function ( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps();
            var state = useListings();
            var count = attributes.limit || attributes.perPage || 6;
            var shown = state.listings.slice( 0, Math.min( count, 6 ) );
            while ( shown.length < Math.min( count, 3 ) ) {
                shown.push( null );
            }

            return el( Fragment, null,
                el( InspectorControls, null,
                    el( components.PanelBody, { title: __( 'Vehicles', 'fleetwire-fleet-management' ) },
                        range( __( 'How many to show', 'fleetwire-fleet-management' ), attributes.limit, 0, 24, function ( limit ) {
                            setAttributes( { limit: limit } );
                        }, __( '0 shows every bookable vehicle.', 'fleetwire-fleet-management' ) ),
                        attributes.limit
                            ? null
                            : range( __( 'Vehicles per page', 'fleetwire-fleet-management' ), attributes.perPage, 0, 24, function ( perPage ) {
                                setAttributes( { perPage: perPage } );
                            }, __( '0 shows them all on one page.', 'fleetwire-fleet-management' ) ),
                        toggle( __( 'Show prices', 'fleetwire-fleet-management' ), attributes.showPrice, function ( showPrice ) {
                            setAttributes( { showPrice: showPrice } );
                        } )
                    ),
                    el( components.PanelBody, { title: __( 'Filters and sorting', 'fleetwire-fleet-management' ) },
                        el( components.ToggleControl, {
                            label: __( 'Let renters filter and sort', 'fleetwire-fleet-management' ),
                            help: __( 'Adds vehicle type, seats, price and sort controls above the vehicles.', 'fleetwire-fleet-management' ),
                            checked: !! attributes.showFilters,
                            onChange: function ( showFilters ) {
                                setAttributes( { showFilters: showFilters } );
                            },
                            __nextHasNoMarginBottom: true,
                        } ),
                        el( components.SelectControl, {
                            label: __( 'Starting order', 'fleetwire-fleet-management' ),
                            value: attributes.sort || 'order',
                            options: SORT_OPTIONS,
                            onChange: function ( sort ) {
                                setAttributes( { sort: sort } );
                            },
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true,
                        } )
                    )
                ),
                el( 'div', blockProps,
                    settings.configured ? null : el( NotConfigured ),
                    attributes.showFilters ? el( 'div', { className: 'fleetwire-preview-search fleetwire-preview-filters' },
                        el( 'span', { className: 'fleetwire-preview-search__field' }, __( 'All types', 'fleetwire-fleet-management' ) ),
                        el( 'span', { className: 'fleetwire-preview-search__field' }, __( 'Any seats', 'fleetwire-fleet-management' ) ),
                        el( 'span', { className: 'fleetwire-preview-search__field' }, __( 'Price', 'fleetwire-fleet-management' ) ),
                        el( 'span', { className: 'fleetwire-preview-search__field' }, sortLabel( attributes.sort ) )
                    ) : null,
                    el( 'div', { className: 'fleetwire-preview-grid' },
                        shown.map( function ( listing, index ) {
                            return el( PreviewCard, { key: index, listing: listing, showPrice: attributes.showPrice } );
                        } )
                    ),
                    el( LiveSiteNote )
                )
            );
        },
        save: function () {
            return null;
        },
    } );

    registerBlockType( 'fleetwire/search', {
        edit: function () {
            return el( 'div', useBlockProps(),
                settings.configured ? null : el( NotConfigured ),
                el( 'div', { className: 'fleetwire-preview-search' },
                    el( 'span', { className: 'fleetwire-preview-search__field' }, __( 'Pick-up date', 'fleetwire-fleet-management' ) ),
                    el( 'span', { className: 'fleetwire-preview-search__field' }, __( 'Return date', 'fleetwire-fleet-management' ) ),
                    el( 'span', { className: 'fleetwire-preview-button' }, __( 'Search', 'fleetwire-fleet-management' ) )
                ),
                el( LiveSiteNote )
            );
        },
        save: function () {
            return null;
        },
    } );

    registerBlockType( 'fleetwire/listing-card', {
        edit: singleVehicleEdit( {
            icon: 'id-alt',
            label: __( 'Fleetwire Vehicle Card', 'fleetwire-fleet-management' ),
            preview: function ( attributes, listing ) {
                return el( 'div', { className: 'fleetwire-preview-single' },
                    el( PreviewCard, { listing: listing, showPrice: attributes.showPrice } )
                );
            },
            controls: function ( attributes, setAttributes ) {
                return toggle( __( 'Show price', 'fleetwire-fleet-management' ), attributes.showPrice, function ( showPrice ) {
                    setAttributes( { showPrice: showPrice } );
                } );
            },
        } ),
        save: function () {
            return null;
        },
    } );

    registerBlockType( 'fleetwire/book-button', {
        edit: singleVehicleEdit( {
            icon: 'cart',
            label: __( 'Fleetwire Book Button', 'fleetwire-fleet-management' ),
            preview: function ( attributes, listing ) {
                var label = attributes.label || __( 'Book Now', 'fleetwire-fleet-management' );
                var price = attributes.showPrice ? formatPrice( listing ).split( '/' )[ 0 ] : '';
                return el( 'span', { className: 'fleetwire-preview-button is-large' }, price ? label + ' - ' + price : label );
            },
            controls: function ( attributes, setAttributes ) {
                return el( Fragment, null,
                    el( components.TextControl, {
                        label: __( 'Button text', 'fleetwire-fleet-management' ),
                        value: attributes.label,
                        placeholder: __( 'Book Now', 'fleetwire-fleet-management' ),
                        onChange: function ( label ) {
                            setAttributes( { label: label } );
                        },
                        __next40pxDefaultSize: true,
                        __nextHasNoMarginBottom: true,
                    } ),
                    toggle( __( 'Show price on the button', 'fleetwire-fleet-management' ), attributes.showPrice, function ( showPrice ) {
                        setAttributes( { showPrice: showPrice } );
                    } )
                );
            },
        } ),
        save: function () {
            return null;
        },
    } );

    registerBlockType( 'fleetwire/listing-gallery', {
        edit: singleVehicleEdit( {
            icon: 'format-gallery',
            label: __( 'Fleetwire Vehicle Gallery', 'fleetwire-fleet-management' ),
            preview: function ( attributes, listing ) {
                var images = ( listing.images && listing.images.length ) ? listing.images : [ listing.image ];
                return el( 'div', { className: 'fleetwire-preview-gallery' },
                    el( Thumb, { className: 'fleetwire-preview-gallery__main', src: images[ 0 ] } ),
                    images.length > 1
                        ? el( 'div', { className: 'fleetwire-preview-gallery__thumbs' },
                            images.map( function ( src, index ) {
                                return el( Thumb, { key: index, className: 'fleetwire-preview-gallery__thumb' + ( index === 0 ? ' is-active' : '' ), src: src } );
                            } ) )
                        : null
                );
            },
        } ),
        save: function () {
            return null;
        },
    } );

    /**
     * Static month grid for the calendar preview: a few sample booked days, since
     * real availability only loads through fleetwire.js on the published page.
     */
    function PreviewMonth( props ) {
        var first = new Date();
        first.setDate( 1 );
        first.setMonth( first.getMonth() + props.offset );
        var daysInMonth = new Date( first.getFullYear(), first.getMonth() + 1, 0 ).getDate();
        var booked = [ 9, 10, 11, 22, 23 ];
        var partial = [ 8, 12, 21, 24 ];
        var cells = [];
        var blank;

        for ( blank = 0; blank < first.getDay(); blank++ ) {
            cells.push( el( 'span', { key: 'blank-' + blank } ) );
        }
        for ( var day = 1; day <= daysInMonth; day++ ) {
            var className = 'fleetwire-preview-calendar__day'
                + ( booked.indexOf( day ) !== -1 ? ' is-booked' : '' )
                + ( partial.indexOf( day ) !== -1 ? ' is-partial' : '' );
            cells.push( el( 'span', { key: day, className: className }, day ) );
        }

        return el( 'div', { className: 'fleetwire-preview-calendar__month' },
            el( 'strong', null, first.toLocaleDateString( undefined, { month: 'long', year: 'numeric' } ) ),
            el( 'div', { className: 'fleetwire-preview-calendar__grid' }, cells )
        );
    }

    registerBlockType( 'fleetwire/listing-calendar', {
        edit: singleVehicleEdit( {
            icon: 'calendar-alt',
            label: __( 'Fleetwire Availability Calendar', 'fleetwire-fleet-management' ),
            preview: function ( attributes, listing ) {
                var months = attributes.months === 1 ? [ 0 ] : [ 0, 1 ];
                return el( 'div', { className: 'fleetwire-preview-calendar' },
                    el( 'p', { className: 'fleetwire-preview-calendar__title' }, listing.name ),
                    el( 'div', { className: 'fleetwire-preview-calendar__months' },
                        months.map( function ( offset ) {
                            return el( PreviewMonth, { key: offset, offset: offset } );
                        } )
                    ),
                    el( 'div', { className: 'fleetwire-preview-calendar__footer' },
                        el( 'span', null, __( 'Booked days are greyed out.', 'fleetwire-fleet-management' ) ),
                        el( 'span', { className: 'fleetwire-preview-button' }, attributes.buttonText || __( 'Book these dates', 'fleetwire-fleet-management' ) )
                    )
                );
            },
            controls: function ( attributes, setAttributes ) {
                return el( Fragment, null,
                    el( components.SelectControl, {
                        label: __( 'Months shown', 'fleetwire-fleet-management' ),
                        value: String( attributes.months || 0 ),
                        options: [
                            { value: '0', label: __( 'Fit to width', 'fleetwire-fleet-management' ) },
                            { value: '1', label: __( 'One month', 'fleetwire-fleet-management' ) },
                            { value: '2', label: __( 'Two months', 'fleetwire-fleet-management' ) },
                        ],
                        onChange: function ( months ) {
                            setAttributes( { months: Number( months ) } );
                        },
                        __next40pxDefaultSize: true,
                        __nextHasNoMarginBottom: true,
                    } ),
                    el( components.TextControl, {
                        label: __( 'Button text', 'fleetwire-fleet-management' ),
                        value: attributes.buttonText,
                        placeholder: __( 'Book these dates', 'fleetwire-fleet-management' ),
                        onChange: function ( buttonText ) {
                            setAttributes( { buttonText: buttonText } );
                        },
                        __next40pxDefaultSize: true,
                        __nextHasNoMarginBottom: true,
                    } )
                );
            },
        } ),
        save: function () {
            return null;
        },
    } );

    registerBlockType( 'fleetwire/listing-details', {
        edit: singleVehicleEdit( {
            icon: 'text-page',
            label: __( 'Fleetwire Vehicle Details', 'fleetwire-fleet-management' ),
            preview: function ( attributes, listing ) {
                var description = listing.description || '';
                if ( attributes.descriptionLength && description.length > attributes.descriptionLength ) {
                    description = description.slice( 0, attributes.descriptionLength - 1 ) + '…';
                }
                var specs = [
                    listing.seats ? sprintf( __( '%d seats', 'fleetwire-fleet-management' ), listing.seats ) : null,
                    listing.doors ? sprintf( __( '%d doors', 'fleetwire-fleet-management' ), listing.doors ) : null,
                ].filter( Boolean ).join( ' · ' );

                return el( 'div', { className: 'fleetwire-preview-details' },
                    el( 'h3', null, listing.name ),
                    el( 'p', { className: 'fleetwire-preview-details__price' }, formatPrice( listing ) ),
                    specs ? el( 'p', null, specs ) : null,
                    attributes.showDescription && description ? el( 'p', null, description ) : null
                );
            },
            controls: function ( attributes, setAttributes ) {
                return el( Fragment, null,
                    toggle( __( 'Show description', 'fleetwire-fleet-management' ), attributes.showDescription, function ( showDescription ) {
                        setAttributes( { showDescription: showDescription } );
                    } ),
                    attributes.showDescription
                        ? range( __( 'Description length', 'fleetwire-fleet-management' ), attributes.descriptionLength, 0, 600, function ( descriptionLength ) {
                            setAttributes( { descriptionLength: descriptionLength } );
                        }, __( 'Characters. 0 shows the full description.', 'fleetwire-fleet-management' ) )
                        : null
                );
            },
        } ),
        save: function () {
            return null;
        },
    } );
}( window.wp ) );
