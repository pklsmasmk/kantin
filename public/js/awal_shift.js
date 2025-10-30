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
        const loginBtn = document.querySelector(this.selectors.loginBtn);
        
        if (!loginBtn) {
            console.error('Login button not found');
            return;
        }
        
        loginBtn.addEventListener('click', (e) => {
            this.handleLoginClick(e, loginBtn);
        });

        loginBtn.addEventListener('keydown', (e) => {
            this.handleKeyboardNavigation(e, loginBtn);
        });
    }

    handleLoginClick(e, loginBtn) {
        e.preventDefault();
        
        this.setLoadingState(loginBtn, true);
        
        setTimeout(() => {
            this.setLoadingState(loginBtn, false);
            this.redirectToLogin();
        }, 1500);
    }

    handleKeyboardNavigation(e, loginBtn) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            loginBtn.click();
        }
    }

    setLoadingState(button, isLoading) {
        if (isLoading) {
            button.classList.add('loading');
            button.setAttribute('aria-label', 'Sedang memproses...');
            button.setAttribute('disabled', 'true');
        } else {
            button.classList.remove('loading');
            button.setAttribute('aria-label', 'Login ke sistem');
            button.removeAttribute('disabled');
        }
    }

    redirectToLogin() {
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.3s ease';
        
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
        const container = document.querySelector(this.selectors.container);
        if (container) {
            container.style.animation = 'fadeInUp 0.8s ease-out';
        }
    }

    animateWarningIcon() {
        const warningIcon = document.querySelector(this.selectors.warningIcon);
        if (warningIcon) {
            setTimeout(() => {
                warningIcon.style.animation = 'pulse 2s infinite';
            }, 500);
        }
    }

    animateFeatures() {
        const features = document.querySelector(this.selectors.features);
        
        if (!features) return;

        if (window.IntersectionObserver) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'slideInUp 0.6s ease-out forwards';
                        observer.unobserve(entry.target);
                    }
                });
            }, { 
                threshold: 0.3,
                rootMargin: '0px 0px -50px 0px'
            });

            observer.observe(features);
        } else {
            this.animateFeaturesFallback(features);
        }
    }

    animateFeaturesFallback(features) {
        setTimeout(() => {
            features.style.animation = 'slideInUp 0.6s ease-out forwards';
        }, 500);
    }

    setupPageTransition() {
        window.addEventListener('beforeunload', () => {
            this.fadeOutPage();
        });

        window.addEventListener('load', () => {
            this.fadeInPage();
        });

        this.fadeInPage();
    }

    fadeOutPage() {
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.2s ease';
    }

    fadeInPage() {
        document.body.style.opacity = '1';
        document.body.style.transition = 'opacity 0.4s ease';
    }

    setupAccessibility() {
        const warningIcon = document.querySelector(this.selectors.warningIcon);
        if (warningIcon) {
            warningIcon.setAttribute('aria-label', 'Ikon akses ditolak');
        }

        window.addEventListener('load', () => {
            const loginBtn = document.querySelector(this.selectors.loginBtn);
            if (loginBtn) {
                loginBtn.focus();
            }
        });
    }

    destroy() {
        const loginBtn = document.querySelector(this.selectors.loginBtn);
        if (loginBtn) {
            loginBtn.replaceWith(loginBtn.cloneNode(true));
        }
    }
}

function handlePageErrors() {
    window.addEventListener('error', (e) => {
        console.error('Error in login required page:', e.error);
        
        const loginBtn = document.querySelector('.login-btn');
        if (loginBtn) {
            const fallbackHandler = (e) => {
                e.preventDefault();
                window.location.href = '?q=login';
            };
            
            loginBtn.addEventListener('click', fallbackHandler);
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
        
        const loginBtn = document.querySelector('.login-btn');
        if (loginBtn) {
            loginBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = '?q=login';
            });
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoginRequiredPage);
} else {
    initLoginRequiredPage();
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { LoginRequired, initLoginRequiredPage };
}