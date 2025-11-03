class LoginRequired {
    constructor() {
        this.selectors = {
            loginBtn: '.login-btn',
            container: '.login-required-container',
            warningIcon: '.warning-icon',
            features: '.features'
        };
        
        this.init();
    }

    init() {
        this.setupLoginButton();
        this.animateElements();
        this.setupPageTransition();
        this.setupAccessibility();
    }

    setupLoginButton() {
        const $loginBtn = $(this.selectors.loginBtn);
        
        if (!$loginBtn.length) {
            console.error('Login button not found');
            return;
        }
        
        $loginBtn.on('click', (e) => {
            this.handleLoginClick(e, $loginBtn);
        });

        $loginBtn.on('keydown', (e) => {
            this.handleKeyboardNavigation(e, $loginBtn);
        });
    }

    handleLoginClick(e, $loginBtn) {
        e.preventDefault();
        
        this.setLoadingState($loginBtn, true);
        
        setTimeout(() => {
            this.setLoadingState($loginBtn, false);
            this.redirectToLogin();
        }, 1500);
    }

    handleKeyboardNavigation(e, $loginBtn) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $loginBtn.trigger('click');
        }
    }

    setLoadingState($button, isLoading) {
        if (isLoading) {
            $button.addClass('loading');
            $button.attr('aria-label', 'Sedang memproses...');
            $button.attr('disabled', 'true');
        } else {
            $button.removeClass('loading');
            $button.attr('aria-label', 'Login ke sistem');
            $button.removeAttr('disabled');
        }
    }

    redirectToLogin() {
        $('body').css({
            'opacity': '0',
            'transition': 'opacity 0.3s ease'
        });
        
        setTimeout(() => {
            window.location.href = '?q=login';
        }, 300);
    }

    animateElements() {
        this.animateContainer();
        this.animateWarningIcon();
        this.animateFeatures();
    }

    animateContainer() {
        const $container = $(this.selectors.container);
        if ($container.length) {
            $container.css('animation', 'fadeInUp 0.8s ease-out');
        }
    }

    animateWarningIcon() {
        const $warningIcon = $(this.selectors.warningIcon);
        if ($warningIcon.length) {
            setTimeout(() => {
                $warningIcon.css('animation', 'pulse 2s infinite');
            }, 500);
        }
    }

    animateFeatures() {
        const $features = $(this.selectors.features);
        
        if (!$features.length) return;

        if (window.IntersectionObserver) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        $(entry.target).css('animation', 'slideInUp 0.6s ease-out forwards');
                        observer.unobserve(entry.target);
                    }
                });
            }, { 
                threshold: 0.3,
                rootMargin: '0px 0px -50px 0px'
            });

            observer.observe($features[0]);
        } else {
            this.animateFeaturesFallback($features);
        }
    }

    animateFeaturesFallback($features) {
        setTimeout(() => {
            $features.css('animation', 'slideInUp 0.6s ease-out forwards');
        }, 500);
    }

    setupPageTransition() {
        $(window).on('beforeunload', () => {
            this.fadeOutPage();
        });

        $(window).on('load', () => {
            this.fadeInPage();
        });

        this.fadeInPage();
    }

    fadeOutPage() {
        $('body').css({
            'opacity': '0',
            'transition': 'opacity 0.2s ease'
        });
    }

    fadeInPage() {
        $('body').css({
            'opacity': '1',
            'transition': 'opacity 0.4s ease'
        });
    }

    setupAccessibility() {
        const $warningIcon = $(this.selectors.warningIcon);
        if ($warningIcon.length) {
            $warningIcon.attr('aria-label', 'Ikon akses ditolak');
        }

        $(window).on('load', () => {
            const $loginBtn = $(this.selectors.loginBtn);
            if ($loginBtn.length) {
                $loginBtn.trigger('focus');
            }
        });
    }

    destroy() {
        const $loginBtn = $(this.selectors.loginBtn);
        if ($loginBtn.length) {
            $loginBtn.off('click keydown');
        }
    }
}

function handlePageErrors() {
    $(window).on('error', (e) => {
        console.error('Error in login required page:', e.error);
        
        const $loginBtn = $('.login-btn');
        if ($loginBtn.length) {
            const fallbackHandler = (e) => {
                e.preventDefault();
                window.location.href = '?q=login';
            };
            
            $loginBtn.off('click').on('click', fallbackHandler);
        }
    });
}

function checkBrowserCompatibility() {
    if (!window.IntersectionObserver) {
        console.warn('IntersectionObserver not supported, using fallback animations');
    }
}

function initLoginRequiredPage() {
    try {
        checkBrowserCompatibility();
        
        handlePageErrors();
        
        const loginPage = new LoginRequired();
        
        window.loginPage = loginPage;
        
    } catch (error) {
        console.error('Failed to initialize login required page:', error);
        
        const $loginBtn = $('.login-btn');
        if ($loginBtn.length) {
            $loginBtn.on('click', (e) => {
                e.preventDefault();
                window.location.href = '?q=login';
            });
        }
    }
}

$(function() {
    initLoginRequiredPage();
});

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { LoginRequired, initLoginRequiredPage };
}