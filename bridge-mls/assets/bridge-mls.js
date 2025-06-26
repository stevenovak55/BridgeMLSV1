/**
 * Bridge MLS Frontend JavaScript
 * Version 3.0.2 - Fixed
 */

(function($) {
    'use strict';

    /**
     * Main Bridge MLS Application Class
     */
    class BridgeMLSApp {
        constructor() {
            this.currentSearchParams = {};
            this.currentImages = [];
            this.currentImageIndex = 0;
            this.currentGalleryIndex = 0;
            this.searchTimeout = null;
            this._isTransitioning = false;
            this._lastSearch = null;
            this._ajaxRequest = null;
            this._touchHandlers = [];
            this._lightboxTouchStartX = 0;
            this._lightboxTouchStartY = 0;
            this._isLightboxSwiping = false;
            this.init();
        }
        
        /**
         * Initialize the application
         */
        init() {
            console.log('Bridge MLS: Initializing application...');
            
            // Prevent multiple initializations
            if (window.bridgeMLSInitialized) {
                console.warn('Bridge MLS: Already initialized');
                return;
            }
            window.bridgeMLSInitialized = true;
            
            try {
                this.bindEvents();
                this.initSelect2();
                this.loadInitialProperties();
                
                // Initialize gallery on property details pages
                if ($('.bridge-property-details-modern').length) {
                    console.log('Bridge MLS: Property details page detected');
                    this.initPropertyDetailsPage();
                }
            } catch (error) {
                console.error('Bridge MLS: Initialization error', error);
            }
        }
        
        /**
         * Bind event handlers with proper cleanup
         */
        bindEvents() {
            // Use namespaced events for easy cleanup
            const ns = '.bridgemls';
            
            // Remove any existing handlers first
            $(document).off(ns);
            
            // Search form events
            $(document).on('click' + ns, '#bridge-search-button', (e) => {
                e.preventDefault();
                this.performSearch();
            });
            
            $(document).on('click' + ns, '#bridge-clear-button', (e) => {
                e.preventDefault();
                this.clearFilters();
            });
            
            // Real-time search with debouncing
            $(document).on('change' + ns, '#bridge-property-search-form select', () => {
                this.performSearch();
            });
            
            $(document).on('input' + ns, '#bridge-property-search-form input[type="number"]', () => {
                this.debouncedSearch();
            });
            
            $(document).on('input' + ns, '#bridge-keywords', () => {
                this.debouncedSearch();
            });
            
            // Gallery navigation
            $(document).on('click' + ns, '.gallery-prev', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.navigateGallery(-1);
            });
            
            $(document).on('click' + ns, '.gallery-next', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.navigateGallery(1);
            });
            
            // Gallery dots navigation
            $(document).on('click' + ns, '.gallery-dot', (e) => {
                e.preventDefault();
                const index = parseInt($(e.currentTarget).data('index')) || 0;
                if (!isNaN(index)) {
                    this.showGalleryImage(index);
                }
            });
            
            // Side image clicks
            $(document).on('click' + ns, '.side-image-container', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const index = parseInt($(e.currentTarget).data('index')) || 0;
                if (!isNaN(index)) {
                    this.showGalleryImage(index);
                }
            });
            
            // Debug mode API test
            if (window.bridgeMLS && window.bridgeMLS.debug) {
                $(document).on('click' + ns, '#bridge-test-api', () => {
                    this.testAPIConnection();
                });
            }
            
            // Contact form submission
            $(document).on('submit' + ns, '.agent-contact-form', (e) => {
                this.handleContactForm(e);
            });
            
            // Mobile contact modal
            $(document).on('click' + ns, '.mobile-contact-button', (e) => {
                e.preventDefault();
                this.openContactModal();
            });
            
            $(document).on('click' + ns, '.modal-close', (e) => {
                e.preventDefault();
                this.closeContactModal();
            });
            
            $(document).on('click' + ns, '.contact-modal-overlay', (e) => {
                e.preventDefault();
                this.closeContactModal();
            });
            
            // Handle browser back/forward buttons
            $(window).on('popstate' + ns, (e) => {
                if (e.originalEvent.state && e.originalEvent.state.searchParams) {
                    this._isTransitioning = true;
                    this.loadSearchParams(e.originalEvent.state.searchParams);
                    setTimeout(() => {
                        this._isTransitioning = false;
                    }, 100);
                }
            });
        }
        
        /**
         * Initialize Select2 with error handling
         */
        initSelect2() {
            if (typeof $.fn.select2 !== 'function') {
                console.warn('Bridge MLS: Select2 not loaded');
                return;
            }
            
            try {
                $('.bridge-multiselect').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            placeholder: 'Select cities...',
                            allowClear: true,
                            width: '100%',
                            closeOnSelect: false
                        });
                    }
                });
                
                // Handle Select2 change events
                $('.bridge-multiselect').off('select2:select.bridgemls select2:unselect.bridgemls')
                    .on('select2:select.bridgemls select2:unselect.bridgemls', () => {
                        this.performSearch();
                    });
            } catch (error) {
                console.error('Bridge MLS: Select2 initialization error', error);
            }
        }
        
        /**
         * Initialize property details page functionality
         */
        initPropertyDetailsPage() {
            console.log('Bridge MLS: Initializing property details page...');
            
            try {
                this.initGalleryEvents();
                this.initLightbox();
                this.initMobileGallery();
                this.initContactModal();
            } catch (error) {
                console.error('Bridge MLS: Property details initialization error', error);
            }
        }
        
        /**
         * Initialize contact modal functionality
         */
        initContactModal() {
            // Check if we're on mobile
            if (window.innerWidth <= 768) {
                // Hide the contact card by default on mobile
                $('#agent-contact-modal').removeClass('active');
                
                // Bind escape key to close modal
                $(document).on('keydown.contactmodal', (e) => {
                    if (e.key === 'Escape' && $('#agent-contact-modal').hasClass('active')) {
                        this.closeContactModal();
                    }
                });
            }
        }
        
        /**
         * Open contact modal
         */
        openContactModal() {
            $('#agent-contact-modal').addClass('active');
            $('.contact-modal-overlay').addClass('active');
            $('body').css('overflow', 'hidden'); // Prevent background scrolling
            
            // Focus on first input for accessibility
            setTimeout(() => {
                $('#agent-contact-modal').find('input:first').focus();
            }, 300);
        }
        
        /**
         * Close contact modal
         */
        closeContactModal() {
            $('#agent-contact-modal').removeClass('active');
            $('.contact-modal-overlay').removeClass('active');
            $('body').css('overflow', ''); // Restore scrolling
        }
        
        /**
         * Initialize gallery event handlers
         */
        initGalleryEvents() {
            // Store current image index
            this.currentGalleryIndex = 0;
            
            // Get all property photos safely
            if (window.propertyPhotos && Array.isArray(window.propertyPhotos) && window.propertyPhotos.length > 0) {
                this.currentImages = window.propertyPhotos;
                console.log('Bridge MLS: Loaded', this.currentImages.length, 'property photos');
            } else {
                console.warn('Bridge MLS: No property photos found');
                this.currentImages = [];
            }
        }
        
        /**
         * Navigate gallery images with bounds checking
         */
        navigateGallery(direction) {
            if (!this.currentImages || this.currentImages.length === 0) {
                console.warn('Bridge MLS: No images to navigate');
                return;
            }
            
            const totalImages = this.currentImages.length;
            const newIndex = (this.currentGalleryIndex + direction + totalImages) % totalImages;
            
            if (newIndex !== this.currentGalleryIndex) {
                this.showGalleryImage(newIndex);
            }
        }
        
        /**
         * Show specific gallery image with validation
         */
        showGalleryImage(index) {
            if (!this.currentImages || !Array.isArray(this.currentImages)) {
                console.error('Bridge MLS: Invalid images array');
                return;
            }
            
            // Validate index
            index = parseInt(index) || 0;
            if (isNaN(index) || index < 0 || index >= this.currentImages.length) {
                console.warn('Bridge MLS: Invalid image index:', index);
                return;
            }
            
            this.currentGalleryIndex = index;
            
            // Update main image
            const $mainImage = $('#main-property-image');
            if ($mainImage.length) {
                const noImageUrl = window.bridgeMLS.no_image_url || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHRleHQtYW5jaG9yPSJtaWRkbGUiIHg9IjIwMCIgeT0iMTUwIiBmaWxsPSIjYWFhIiBmb250LXNpemU9IjIwIiBmb250LWZhbWlseT0iQXJpYWwiPk5vIEltYWdlIEF2YWlsYWJsZTwvdGV4dD48L3N2Zz4=';
                
                $mainImage
                    .attr('src', this.currentImages[index])
                    .attr('data-index', index)
                    .off('error')
                    .on('error', function() {
                        // Prevent infinite loop by checking if we're already showing the no-image placeholder
                        if ($(this).attr('src') !== noImageUrl) {
                            $(this).attr('src', noImageUrl);
                        }
                    });
            }
            
            // Update dots
            $('.gallery-dot').removeClass('active')
                .filter(`[data-index="${index}"]`).addClass('active');
            
            // Update mobile gallery
            this.updateMobileGallery(index);
        }
        
        /**
         * Initialize mobile gallery with improved touch handling
         */
        initMobileGallery() {
            const $container = $('.mobile-gallery-container');
            if (!$container.length) return;
            
            let touchStartX = 0;
            let touchEndX = 0;
            let isSwiping = false;
            
            // Remove old handlers
            $container.off('.mobileswipe');
            
            // Touch start
            $container.on('touchstart.mobileswipe', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                isSwiping = true;
            });
            
            // Touch move (prevent scrolling during swipe)
            $container.on('touchmove.mobileswipe', (e) => {
                if (isSwiping) {
                    e.preventDefault();
                }
            });
            
            // Touch end
            $container.on('touchend.mobileswipe', (e) => {
                if (!isSwiping) return;
                
                touchEndX = e.changedTouches[0].screenX;
                this.handleMobileSwipe(touchStartX, touchEndX);
                isSwiping = false;
            });
            
            // Touch cancel
            $container.on('touchcancel.mobileswipe', () => {
                isSwiping = false;
            });
        }
        
        /**
         * Handle mobile swipe with threshold
         */
        handleMobileSwipe(startX, endX) {
            const threshold = 50;
            const diff = startX - endX;
            
            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    // Swipe left - next image
                    this.navigateGallery(1);
                } else {
                    // Swipe right - previous image
                    this.navigateGallery(-1);
                }
            }
        }
        
        /**
         * Update mobile gallery position with animation
         */
        updateMobileGallery(index) {
            const $track = $('.mobile-gallery-track');
            if (!$track.length) return;
            
            const translateX = -index * 100;
            $track.css({
                'transform': `translateX(${translateX}%)`,
                'transition': 'transform 0.3s ease'
            }).attr('data-current', index);
        }
        
        /**
         * Initialize lightbox with improved functionality
         */
        initLightbox() {
            console.log('Bridge MLS: Initializing lightbox...');
            
            // Create lightbox HTML if it doesn't exist
            if (!$('#gallery-lightbox').length) {
                const lightboxHTML = `
                    <div id="gallery-lightbox" class="gallery-lightbox" role="dialog" aria-modal="true" aria-label="Image gallery">
                        <div class="lightbox-container">
                            <button class="lightbox-close" aria-label="Close gallery">&times;</button>
                            <div class="lightbox-image-wrapper">
                                <img class="lightbox-image" src="" alt="Property photo">
                                <div class="swipe-indicator swipe-left">&#8249;</div>
                                <div class="swipe-indicator swipe-right">&#8250;</div>
                            </div>
                            <button class="lightbox-nav lightbox-prev" aria-label="Previous image">&#8249;</button>
                            <button class="lightbox-nav lightbox-next" aria-label="Next image">&#8250;</button>
                            <div class="lightbox-counter" aria-live="polite"></div>
                        </div>
                    </div>
                `;
                $('body').append(lightboxHTML);
            }
            
            // Bind lightbox events
            this.bindLightboxEvents();
            
            // Initialize touch support
            this.initLightboxTouch();
        }
        
        /**
         * Initialize lightbox touch events
         */
        initLightboxTouch() {
            const $lightbox = $('#gallery-lightbox');
            const $imageWrapper = $('.lightbox-image-wrapper');
            
            // Detect touch device
            if ('ontouchstart' in window) {
                $('body').addClass('touch-device');
            }
            
            // Touch events for swipe
            $imageWrapper.on('touchstart.lightboxtouch', (e) => {
                this._lightboxTouchStartX = e.changedTouches[0].screenX;
                this._lightboxTouchStartY = e.changedTouches[0].screenY;
                this._isLightboxSwiping = true;
                $imageWrapper.addClass('swiping');
            });
            
            $imageWrapper.on('touchmove.lightboxtouch', (e) => {
                if (!this._isLightboxSwiping) return;
                
                const touchX = e.changedTouches[0].screenX;
                const touchY = e.changedTouches[0].screenY;
                const diffX = touchX - this._lightboxTouchStartX;
                const diffY = touchY - this._lightboxTouchStartY;
                
                // If vertical movement is greater, allow scroll
                if (Math.abs(diffY) > Math.abs(diffX)) {
                    this._isLightboxSwiping = false;
                    $imageWrapper.removeClass('swiping');
                    return;
                }
                
                // Prevent vertical scroll during horizontal swipe
                e.preventDefault();
                
                // Show swipe indicators
                if (diffX > 50) {
                    $('.swipe-left').css('opacity', '0.5');
                    $('.swipe-right').css('opacity', '0');
                } else if (diffX < -50) {
                    $('.swipe-right').css('opacity', '0.5');
                    $('.swipe-left').css('opacity', '0');
                } else {
                    $('.swipe-indicator').css('opacity', '0');
                }
            });
            
            $imageWrapper.on('touchend.lightboxtouch', (e) => {
                if (!this._isLightboxSwiping) return;
                
                const touchEndX = e.changedTouches[0].screenX;
                const diffX = this._lightboxTouchStartX - touchEndX;
                
                $imageWrapper.removeClass('swiping');
                $('.swipe-indicator').css('opacity', '0');
                
                // Swipe threshold
                if (Math.abs(diffX) > 50) {
                    if (diffX > 0) {
                        this.nextImage();
                    } else {
                        this.previousImage();
                    }
                }
                
                this._isLightboxSwiping = false;
            });
            
            $imageWrapper.on('touchcancel.lightboxtouch', () => {
                this._isLightboxSwiping = false;
                $imageWrapper.removeClass('swiping');
                $('.swipe-indicator').css('opacity', '0');
            });
        }
        
        /**
         * Bind lightbox event handlers with cleanup
         */
        bindLightboxEvents() {
            const ns = '.lightbox';
            
            // Remove existing handlers
            $(document).off(ns);
            $('#gallery-lightbox').off(ns);
            
            // Image clicks
            $(document).on('click' + ns, '#main-property-image, .side-image-container img', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const $clickedElement = $(e.currentTarget);
                const index = $clickedElement.closest('[data-index]').data('index') || 
                             $clickedElement.data('index') || 
                             this.currentGalleryIndex || 0;
                this.openLightbox(index);
            });
            
            // View all photos button
            $(document).on('click' + ns, '.view-all-photos', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.openLightbox(0);
            });
            
            // Navigation
            $('#gallery-lightbox').on('click' + ns, '.lightbox-prev', (e) => {
                e.stopPropagation();
                this.previousImage();
            });
            
            $('#gallery-lightbox').on('click' + ns, '.lightbox-next', (e) => {
                e.stopPropagation();
                this.nextImage();
            });
            
            // Close events
            $('#gallery-lightbox').on('click' + ns, '.lightbox-close', (e) => {
                e.stopPropagation();
                this.closeLightbox();
            });
            
            $('#gallery-lightbox').on('click' + ns, (e) => {
                if (e.target.id === 'gallery-lightbox' || $(e.target).hasClass('lightbox-container')) {
                    this.closeLightbox();
                }
            });
            
            // Keyboard navigation
            $(document).on('keydown' + ns, (e) => {
                if ($('#gallery-lightbox').hasClass('show')) {
                    switch(e.key) {
                        case 'Escape':
                            e.preventDefault();
                            this.closeLightbox();
                            break;
                        case 'ArrowLeft':
                            e.preventDefault();
                            this.previousImage();
                            break;
                        case 'ArrowRight':
                            e.preventDefault();
                            this.nextImage();
                            break;
                    }
                }
            });
        }
        
        /**
         * Open lightbox with validation
         */
        openLightbox(index) {
            console.log('Bridge MLS: Opening lightbox at index:', index);
            
            if (!this.currentImages || this.currentImages.length === 0) {
                console.error('Bridge MLS: No images available for lightbox');
                return;
            }
            
            // Validate index
            index = parseInt(index) || 0;
            if (index < 0 || index >= this.currentImages.length) {
                index = 0;
            }
            
            this.currentImageIndex = index;
            this.updateLightboxImage();
            
            // Show lightbox with animation
            const $lightbox = $('#gallery-lightbox');
            $lightbox.addClass('show');
            $('body').addClass('lightbox-open').css('overflow', 'hidden');
            
            // Focus management for accessibility
            setTimeout(() => {
                $lightbox.find('.lightbox-close').focus();
            }, 100);
        }
        
        /**
         * Update lightbox image display with error handling
         */
        updateLightboxImage() {
            if (!this.currentImages || this.currentImages.length === 0) return;
            
            const currentImage = this.currentImages[this.currentImageIndex];
            const $lightboxImage = $('.lightbox-image');
            const $imageWrapper = $('.lightbox-image-wrapper');
            const noImageUrl = window.bridgeMLS.no_image_url || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHRleHQtYW5jaG9yPSJtaWRkbGUiIHg9IjIwMCIgeT0iMTUwIiBmaWxsPSIjYWFhIiBmb250LXNpemU9IjIwIiBmb250LWZhbWlseT0iQXJpYWwiPk5vIEltYWdlIEF2YWlsYWJsZTwvdGV4dD48L3N2Zz4=';
            
            // Add loading state
            $imageWrapper.addClass('image-loading');
            
            // Update image with loading state
            $lightboxImage
                .css('opacity', '0')
                .attr('src', currentImage)
                .attr('alt', `Property photo ${this.currentImageIndex + 1}`)
                .off('load error')
                .on('load', function() {
                    $imageWrapper.removeClass('image-loading');
                    $(this).css('opacity', '1');
                })
                .on('error', function() {
                    $imageWrapper.removeClass('image-loading');
                    // Prevent infinite loop by checking if we're already showing the no-image placeholder
                    if ($(this).attr('src') !== noImageUrl) {
                        $(this).attr('src', noImageUrl)
                            .css('opacity', '1');
                    }
                });
            
            // Update counter
            $('.lightbox-counter').text(`${this.currentImageIndex + 1} / ${this.currentImages.length}`);
            
            // Show/hide navigation buttons based on image count and device type
            const showNav = this.currentImages.length > 1 && !$('body').hasClass('touch-device');
            $('.lightbox-prev, .lightbox-next').toggle(showNav);
        }
        
        /**
         * Show previous image in lightbox
         */
        previousImage() {
            if (this.currentImages.length <= 1) return;
            this.currentImageIndex = (this.currentImageIndex - 1 + this.currentImages.length) % this.currentImages.length;
            this.updateLightboxImage();
        }
        
        /**
         * Show next image in lightbox
         */
        nextImage() {
            if (this.currentImages.length <= 1) return;
            this.currentImageIndex = (this.currentImageIndex + 1) % this.currentImages.length;
            this.updateLightboxImage();
        }
        
        /**
         * Close lightbox with cleanup
         */
        closeLightbox() {
            const $lightbox = $('#gallery-lightbox');
            $lightbox.removeClass('show');
            $('body').removeClass('lightbox-open').css('overflow', '');
            
            // Clear image source to prevent memory leaks
            setTimeout(() => {
                $('.lightbox-image').attr('src', '');
            }, 300);
        }
        
        /**
         * Load initial properties based on URL parameters
         */
        loadInitialProperties() {
            if (window.bridgeInitialParams) {
                this.loadSearchParams(window.bridgeInitialParams);
            }
        }
        
        /**
         * Load search parameters into form
         */
        loadSearchParams(params) {
            this.currentSearchParams = params;
            
            // Set form values from params
            if (params.city) {
                const cities = Array.isArray(params.city) ? 
                    params.city : 
                    params.city.split(',').map(c => c.trim());
                $('#bridge-city').val(cities).trigger('change');
            }
            
            // Set other fields
            ['min_price', 'max_price', 'bedrooms', 'bathrooms', 'property_type', 'keywords'].forEach(field => {
                if (params[field]) {
                    $(`#bridge-${field.replace('_', '-')}`).val(params[field]);
                }
            });
        }
        
        /**
         * Perform property search with improved error handling
         */
        performSearch(updateURL = true) {
            console.log('Bridge MLS: Performing search...');
            
            // Cancel any pending request
            if (this._ajaxRequest && this._ajaxRequest.readyState !== 4) {
                this._ajaxRequest.abort();
            }
            
            // Build search parameters
            const searchParams = this.buildSearchParams();
            
            // Check if this is the same as the last search
            const searchKey = JSON.stringify(searchParams);
            if (this._lastSearch === searchKey) {
                console.log('Bridge MLS: Duplicate search prevented');
                return;
            }
            this._lastSearch = searchKey;
            
            this.currentSearchParams = searchParams;
            
            // Update URL if requested
            if (updateURL && !this._isTransitioning) {
                this.updateURL(searchParams);
            }
            
            // Show loading state
            this.showLoadingState();
            
            // Make AJAX request
            this._ajaxRequest = $.ajax({
                url: window.bridgeMLS.ajax_url,
                type: 'POST',
                data: {
                    action: 'bridge_search_properties',
                    nonce: window.bridgeMLS.nonce,
                    ...searchParams
                },
                success: (response) => {
                    if (response.success) {
                        this.displaySearchResults(response.data);
                    } else {
                        this.displayError(response.data || 'Search failed. Please try again.');
                    }
                },
                error: (xhr, status, error) => {
                    if (status === 'abort') {
                        return; // Request was cancelled
                    }
                    
                    let errorMessage = 'Connection error. Please check your internet connection.';
                    
                    if (xhr.status === 404) {
                        errorMessage = 'The search endpoint could not be found. Please contact support.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error occurred. Please try again later.';
                    } else if (xhr.status === 401) {
                        errorMessage = 'Authentication failed. Please check your API credentials.';
                    }
                    
                    this.displayError(errorMessage);
                },
                complete: () => {
                    this.hideLoadingState();
                    
                    // Reset last search after a delay
                    setTimeout(() => {
                        this._lastSearch = null;
                    }, 500);
                }
            });
        }
        
        /**
         * Build search parameters from form
         */
        buildSearchParams() {
            const params = {
                city: $('#bridge-city').val(),
                min_price: $('#bridge-min-price').val(),
                max_price: $('#bridge-max-price').val(),
                bedrooms: $('#bridge-bedrooms').val(),
                bathrooms: $('#bridge-bathrooms').val(),
                property_type: $('#bridge-property-type').val(),
                keywords: $('#bridge-keywords').val()
            };
            
            // Remove empty values
            Object.keys(params).forEach(key => {
                if (!params[key] || params[key] === 'any' || 
                    (Array.isArray(params[key]) && params[key].length === 0)) {
                    delete params[key];
                }
            });
            
            return params;
        }
        
        /**
         * Show loading state
         */
        showLoadingState() {
            $('#bridge-loading').fadeIn(200);
            $('#bridge-search-results').css('opacity', '0.5');
            $('#bridge-search-button').prop('disabled', true);
        }
        
        /**
         * Hide loading state
         */
        hideLoadingState() {
            $('#bridge-loading').fadeOut(200);
            $('#bridge-search-results').css('opacity', '1');
            $('#bridge-search-button').prop('disabled', false);
        }
        
        /**
         * Debounced search for input fields
         */
        debouncedSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.performSearch();
            }, 500);
        }
        
        /**
         * Display search results with animation
         */
        displaySearchResults(data) {
            const $resultsContainer = $('#bridge-search-results .property-grid');
            
            if (data.html) {
                // Fade out old results
                $resultsContainer.fadeOut(200, () => {
                    // Update content
                    $resultsContainer.html(data.html);
                    
                    // Animate new results
                    $resultsContainer.find('.property-card').each(function(index) {
                        $(this).css({
                            'opacity': '0',
                            'transform': 'translateY(20px)'
                        }).delay(index * 50).animate({
                            'opacity': '1'
                        }, 300).css('transform', 'translateY(0)');
                    });
                    
                    // Fade in container
                    $resultsContainer.fadeIn(200);
                });
            } else if (data.count === 0) {
                $resultsContainer.html('<p class="no-properties">No properties found matching your criteria. Try adjusting your filters.</p>');
            }
            
            // Update result count if available
            if (data.count !== undefined) {
                console.log(`Bridge MLS: Found ${data.count} properties`);
                
                // Update any result count displays
                const $countDisplay = $('.results-count');
                if ($countDisplay.length) {
                    $countDisplay.text(`${data.count} properties found`);
                }
            }
        }
        
        /**
         * Display error message
         */
        displayError(message) {
            const $resultsContainer = $('#bridge-search-results .property-grid');
            $resultsContainer.html(`
                <div class="error">
                    <strong>Error:</strong> ${message}
                    <button class="button button-secondary" onclick="window.bridgeMLSApp.performSearch()">
                        Try Again
                    </button>
                </div>
            `);
        }
        
        /**
         * Clear all filters and reset form
         */
        clearFilters() {
            // Reset form
            $('#bridge-property-search-form')[0].reset();
            
            // Clear Select2
            $('#bridge-city').val(null).trigger('change');
            
            // Clear search params
            this.currentSearchParams = {};
            
            // Perform new search
            this.performSearch();
        }
        
        /**
         * Update URL with search parameters
         */
        updateURL(searchParams) {
            if (this._isTransitioning) return;
            
            try {
                const url = new URL(window.location);
                
                // Clear existing params
                ['city', 'min_price', 'max_price', 'bedrooms', 'bathrooms', 'property_type', 'keywords'].forEach(param => {
                    url.searchParams.delete(param);
                });
                
                // Add new params
                Object.keys(searchParams).forEach(key => {
                    if (Array.isArray(searchParams[key])) {
                        url.searchParams.set(key, searchParams[key].join(','));
                    } else {
                        url.searchParams.set(key, searchParams[key]);
                    }
                });
                
                // Update browser history
                window.history.replaceState({searchParams: searchParams}, '', url);
            } catch (error) {
                console.error('Bridge MLS: Error updating URL', error);
            }
        }
        
        /**
         * Handle contact form submission
         */
        handleContactForm(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitButton = $form.find('button[type="submit"]');
            const propertyAddress = $form.data('property');
            
            // Get form data
            const formData = {
                name: $form.find('[name="name"]').val().trim(),
                email: $form.find('[name="email"]').val().trim(),
                phone: $form.find('[name="phone"]').val().trim(),
                message: $form.find('[name="message"]').val().trim(),
                property: propertyAddress
            };
            
            // Validate form
            if (!formData.name || !formData.email) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formData.email)) {
                alert('Please enter a valid email address.');
                return;
            }
            
            // Show loading state
            const originalText = $submitButton.text();
            $submitButton.prop('disabled', true).text('Sending...');
            
            // Send AJAX request
            $.ajax({
                url: window.bridgeMLS.ajax_url,
                type: 'POST',
                data: {
                    action: 'bridge_mls_contact_agent',
                    nonce: window.bridgeMLS.nonce,
                    ...formData
                },
                success: (response) => {
                    if (response.success) {
                        alert(`Thank you for your interest in ${propertyAddress}. An agent will contact you soon!`);
                        $form[0].reset();
                        
                        // Close modal on mobile
                        if (window.innerWidth <= 768) {
                            this.closeContactModal();
                        }
                    } else {
                        alert(response.data || 'Failed to send message. Please try again.');
                    }
                },
                error: () => {
                    alert('Connection error. Please try again later.');
                },
                complete: () => {
                    $submitButton.prop('disabled', false).text(originalText);
                }
            });
        }
        
        /**
         * Test API connection (debug mode)
         */
        testAPIConnection() {
            const $statusDiv = $('#bridge-api-status');
            const $testButton = $('#bridge-test-api');
            
            $statusDiv.html('<em>Testing API connection...</em>');
            $testButton.prop('disabled', true);
            
            $.ajax({
                url: window.bridgeMLS.ajax_url,
                type: 'POST',
                data: {
                    action: 'bridge_test_api',
                    nonce: window.bridgeMLS.nonce
                },
                success: (response) => {
                    if (response.success) {
                        let html = `<div style="color: green; margin-top: 10px;">
                            <strong>✓ ${response.data.message}</strong>`;
                        
                        if (response.data.tests) {
                            html += '<ul style="margin-top: 10px;">';
                            for (let key in response.data.tests) {
                                const test = response.data.tests[key];
                                const icon = test.success ? '✅' : '❌';
                                html += `<li>${icon} ${test.name}: ${test.message}</li>`;
                            }
                            html += '</ul>';
                        }
                        
                        html += '</div>';
                        $statusDiv.html(html);
                    } else {
                        $statusDiv.html(`<div style="color: red; margin-top: 10px;">
                            <strong>✗ API Test Failed:</strong> ${response.data}
                        </div>`);
                    }
                },
                error: () => {
                    $statusDiv.html(`<div style="color: red; margin-top: 10px;">
                        <strong>✗ Connection Error:</strong> Could not reach the server.
                    </div>`);
                },
                complete: () => {
                    $testButton.prop('disabled', false);
                }
            });
        }
        
        /**
         * Cleanup method for destroying the app
         */
        destroy() {
            // Clear timeouts
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            
            // Abort pending requests
            if (this._ajaxRequest && this._ajaxRequest.readyState !== 4) {
                this._ajaxRequest.abort();
            }
            
            // Remove event listeners
            $(document).off('.bridgemls');
            $(window).off('.bridgemls');
            $('#gallery-lightbox').off('.lightbox');
            $('.mobile-gallery-container').off('.mobileswipe');
            $('.lightbox-image-wrapper').off('.lightboxtouch');
            $(document).off('.contactmodal');
            
            // Destroy Select2
            $('.bridge-multiselect').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            
            // Reset state
            window.bridgeMLSInitialized = false;
        }
    }
    
    /**
     * Initialize app when DOM is ready
     */
    $(document).ready(function() {
        // Initialize main app
        window.bridgeMLSApp = new BridgeMLSApp();
        
        // Debug functions if enabled
        if (window.bridgeMLS && window.bridgeMLS.debug) {
            window.bridgeDebug = {
                app: window.bridgeMLSApp,
                testAPI: function() {
                    return window.bridgeMLSApp.testAPIConnection();
                },
                getCurrentParams: function() {
                    return window.bridgeMLSApp.currentSearchParams;
                },
                getImages: function() {
                    return window.bridgeMLSApp.currentImages;
                },
                openLightbox: function(index = 0) {
                    return window.bridgeMLSApp.openLightbox(index);
                },
                destroy: function() {
                    if (window.bridgeMLSApp) {
                        window.bridgeMLSApp.destroy();
                        window.bridgeMLSApp = null;
                    }
                }
            };
            console.log('Bridge MLS: Debug functions available in window.bridgeDebug');
        }
    });
    
    /**
     * Global utility functions
     */
    window.BridgeMLSUtils = {
        search: function(params) {
            if (window.bridgeMLSApp) {
                window.bridgeMLSApp.currentSearchParams = params || {};
                return window.bridgeMLSApp.performSearch();
            }
        },
        
        showProperty: function(listingKey) {
            console.log('Loading property:', listingKey);
            // Navigate to property details page
            window.location.href = window.bridgeMLS.home_url + '/property-details/?listing_key=' + listingKey;
        },
        
        shareProperty: function() {
            const url = window.location.href;
            const title = document.title;
            
            if (navigator.share) {
                // Use native share API if available
                navigator.share({
                    title: title,
                    url: url
                }).catch(err => {
                    if (err.name !== 'AbortError') {
                        console.log('Error sharing:', err);
                        this.fallbackShare(url);
                    }
                });
            } else {
                this.fallbackShare(url);
            }
        },
        
        fallbackShare: function(url) {
            // Create temporary input
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(url).select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    // Show success message
                    this.showNotification('Property link copied to clipboard!');
                } else {
                    throw new Error('Copy command failed');
                }
            } catch (err) {
                // Fallback to prompt
                prompt('Copy this link:', url);
            }
            
            $temp.remove();
        },
        
        showNotification: function(message, type = 'success') {
            // Remove existing notifications
            $('.bridge-notification').remove();
            
            // Create notification
            const $notification = $(`
                <div class="bridge-notification bridge-notification-${type}">
                    ${message}
                </div>
            `);
            
            // Add to body
            $('body').append($notification);
            
            // Animate in
            setTimeout(() => {
                $notification.addClass('show');
            }, 10);
            
            // Remove after 3 seconds
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }, 3000);
        }
    };

})(jQuery);