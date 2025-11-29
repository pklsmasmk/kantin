class LoginRequired {
    constructor() {
        this.selectors = {
            loginBtn: '.login-btn',
            container: '.login-required-container',
            warningIcon: '.warning-icon',
            features: '.features'
        };
        
        this.initialized = false;
        this.init();
    }

    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.initializeComponents();
            });
        } else {
            setTimeout(() => {
                this.initializeComponents();
            }, 100);
        }
    }

    initializeComponents() {
        if (this.initialized) return;
        
        this.setupLoginButton();
        this.animateElements();
        this.setupPageTransition();
        this.setupAccessibility();
        
        this.initialized = true;
    }

    setupLoginButton() {
        let $loginBtn = $(this.selectors.loginBtn);
        
        if (!$loginBtn.length) {
            $loginBtn = $('a[href*="login"], .btn-login, [class*="login"]');
        }
        
        if (!$loginBtn.length) {
            $('a, button').each(function() {
                const $el = $(this);
                const text = $el.text().toLowerCase().trim();
                if (text.includes('login') || text.includes('masuk')) {
                    $loginBtn = $el;
                    return false;
                }
            });
        }
        
        if (!$loginBtn.length) {
            this.setupEventDelegation();
            return;
        }
        
        $loginBtn.off('click.required keydown.required');
        
        $loginBtn.on('click.required', (e) => {
            this.handleLoginClick(e, $loginBtn);
        });

        $loginBtn.on('keydown.required', (e) => {
            this.handleKeyboardNavigation(e, $loginBtn);
        });
        
        $loginBtn.attr('data-login-handler', 'attached');
    }

    setupEventDelegation() {
        $(document).off('click.required').on('click.required', this.selectors.loginBtn, (e) => {
            e.preventDefault();
            this.handleLoginClick(e, $(e.target));
        });
        
        $(document).off('click.requiredAlt').on('click.requiredAlt', 'a[href*="login"]', (e) => {
            e.preventDefault();
            this.handleLoginClick(e, $(e.target));
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
            $button.prop('disabled', true);
            $button.html('<span class="loading-spinner"></span> Memproses...');
        } else {
            $button.removeClass('loading');
            $button.attr('aria-label', 'Login ke sistem');
            $button.prop('disabled', false);
            $button.text('Login Sekarang');
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
        $(window).on('beforeunload.required', () => {
            this.fadeOutPage();
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

        setTimeout(() => {
            const $loginBtn = $(this.selectors.loginBtn);
            if ($loginBtn.length) {
                $loginBtn.trigger('focus');
            }
        }, 1000);
    }

    destroy() {
        $(this.selectors.loginBtn).off('click.required keydown.required');
        $(document).off('click.required click.requiredAlt');
        $(window).off('beforeunload.required');
        
        this.initialized = false;
    }
}

function handlePageErrors() {
    $(window).on('error.required', (e) => {
        $('.login-btn').off('click').on('click', function(e) {
            e.preventDefault();
            window.location.href = '?q=login';
        });
    });
}

function checkBrowserCompatibility() {
    if (typeof jQuery === 'undefined') {
        return false;
    }
    return true;
}

function initLoginRequiredPage() {
    if (typeof jQuery === 'undefined') {
        setupFallbackBehavior();
        return;
    }
    
    try {
        if (!checkBrowserCompatibility()) {
            setupFallbackBehavior();
            return;
        }
        
        handlePageErrors();
        
        const loginPage = new LoginRequired();
        window.loginPage = loginPage;
        
    } catch (error) {
        setupFallbackBehavior();
    }
}

function setupFallbackBehavior() {
    document.addEventListener('DOMContentLoaded', function() {
        const loginButtons = document.querySelectorAll('.login-btn, a[href*="login"]');
        
        loginButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = '?q=login';
            });
        });
    });
}

$(function() {
    initLoginRequiredPage();
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoginRequiredPage);
} else {
    setTimeout(initLoginRequiredPage, 100);
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { LoginRequired, initLoginRequiredPage };
}