jQuery( document ).ready( function () {
    //document.getElementById('.tied-pages-dropdown-disabled').disabled = true;
    var counter = 0;
    var int_new_tied_page_id = 0;
    
    var tied_page_node = document.querySelector( '#tied_page_id' );
	var tied_page_title_node = document.querySelector( '#tied_pages_different_title' );
    
	if ( tied_page_node != null ) {
        tied_page_node.addEventListener( "change", tiedPageChangeEventHandler );
	} else if ( tied_page_title_node != null ) {
        tied_page_title_node.addEventListener( "change", tiedPageChangeEventHandler );
    }
    
    function tiedPageChangeEventHandler( e ) {
        int_new_tied_page_id = jQuery( "#" + e.target.id ).val();

        if ( isGutenberg() ) {
            if ( jQuery( '#tied_page_id' ).val() != '' ) {
                createAdminInfo();
            } else {
                removeAdminInfo( 'tied-page-info' );
            }
        }
    }
    
    if ( isGutenberg() && ( document.querySelector( '#tied_page_id' ) != null 
        || document.querySelector( '#tied_pages_different_title' ) != null ) ) {
        if ( jQuery( '#tied_page_id' ).val() != '' ) {
            createAdminInfo();
        }
        
        const unsubscribe = wp.data.subscribe( function () {
            var isSavingPost = wp.data.select( 'core/editor' ).isSavingPost();
            var isAutosavingPost = wp.data.select( 'core/editor' ).isAutosavingPost();

            if ( isSavingPost && !isAutosavingPost ) {
                if ( int_new_tied_page_id != 0 ) {
                    unsubscribe();
                    setTimeoutForReload( 500 );
                }
            }

            if ( document.querySelector( '#tied_page_id' ) != null ) {
                document.querySelector( '#tied_page_id' ).addEventListener( "change", tiedPageChangeEventHandler );
            }
            
            if ( document.querySelector( '#tied_pages_different_title' ) != null ) {
                document.querySelector( '#tied_pages_different_title' ).addEventListener( "change", tiedPageChangeEventHandler );
            }

        });
    }

    function createAdminInfo() {
        wp.data.dispatch( 'core/notices' ).createNotice(
            'info', // Can be one of: success, info, warning, error.
            tp_translations.notice_title, // Text string to display.
            {
                id: 'tied-page-info',
                isDismissible: false, // Whether the user can dismiss the notice.
                // Any actions the user can perform.
                actions: [
                    {
                        url: 'post.php?post=' + jQuery( '#tied_page_id' ).val() + '&action=edit',
                        label: tp_translations.view_master_page
                    },
                    {
                        url: 'javascript:scrollToEvent(jQuery("#tied-page-metabox"));',
                        label: tp_translations.view_tied_page_menu
                    }
                ]
            }
        );
    }

    function removeAdminInfo( notice_id ) {
        wp.data.dispatch( 'core/notices' ).removeNotice( notice_id );
    }

    function setTimeoutForReload( timeout ) {
        setTimeout( reloadHandler, timeout );
    }

    function reloadHandler() {
        counter++;
        // abort the loop  
        if ( counter < 200 ) {
            if ( !wp.data.select( 'core/editor' ).isEditedPostDirty() ) {
                // Post is saved
                setTimeout( function(){
                    window.location.href = window.location.href;
                }, 2000 );
            } else {
                setTimeoutForReload( 100 );
            }
        }
    }

});
