(function() {
    'use strict';
    
    const init = () => {
        setupImageLoading();
        setupScrollIndicator();
        setupCTATracking();
        setupKeyboardNavigation();
    };
    
    const setupImageLoading = () => {
        const heroImage = document.querySelector('.hero__image');
        if (!heroImage) return;
        
        if (heroImage.complete) {
            heroImage.classList.add('is-loaded');
        } else {
            heroImage.addEventListener('load', () => {
                heroImage.classList.add('is-loaded');
            }, { once: true });
        }
    };
    
    const setupScrollIndicator = () => {
        const scrollIndicator = document.querySelector('.hero__scroll');
        if (!scrollIndicator) return;
        
        let hasScrolled = false;
        
        const handleScroll = () => {
            if (hasScrolled) return;
            
            if (window.scrollY > 100) {
                hasScrolled = true;
                scrollIndicator.style.opacity = '0';
                scrollIndicator.style.transform = 'translateX(-50%) translateY(20px)';
                window.removeEventListener('scroll', handleScroll);
            }
        };
        
        window.addEventListener('scroll', handleScroll, { passive: true });
    };
    
    const setupCTATracking = () => {
        const primaryCTA = document.querySelector('.hero__btn--primary');
        const secondaryCTA = document.querySelector('.hero__sub-cta-link');
        
        const trackClick = (label, destination) => {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'cta_click', {
                    'event_category': 'hero_section',
                    'event_label': label,
                    'destination': destination,
                    'transport_type': 'beacon'
                });
            }
            
            if (typeof dataLayer !== 'undefined') {
                dataLayer.push({
                    'event': 'hero_cta_click',
                    'cta_label': label,
                    'cta_destination': destination
                });
            }
        };
        
        if (primaryCTA) {
            primaryCTA.addEventListener('click', () => {
                trackClick('primary_search', primaryCTA.href);
            });
        }
        
        if (secondaryCTA) {
            secondaryCTA.addEventListener('click', () => {
                trackClick('secondary_diagnosis', secondaryCTA.href);
            });
        }
    };
    
    const setupKeyboardNavigation = () => {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
        });
        
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
