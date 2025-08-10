/**
 * AI Interview Widget - Geolocation Module (aiw-geo.js)
 * Version: 1.9.4+
 * 
 * Privacy-conscious, resilient geolocation abstraction layer
 * Eliminates noisy CORS errors from multiple IP services
 * Provides caching, opt-out mechanisms, and graceful fallbacks
 */

(function(window) {
    'use strict';

    // Configuration defaults
    const DEFAULT_CONFIG = {
        enabled: true,
        useCache: true,
        cacheTimeoutMs: 24 * 60 * 60 * 1000, // 24 hours
        networkTimeoutMs: 8000, // 8 seconds
        debugMode: false,
        privacy: {
            requireConsent: false,
            consentStorageKey: 'aiw_geo_consent'
        },
        fallbackToTimezone: true,
        silentErrors: true // Silent in production, unless debugMode is true
    };

    // Cache keys
    const CACHE_KEYS = {
        country: 'aiw_geo_country',
        timestamp: 'aiw_geo_timestamp',
        consent: 'aiw_geo_consent'
    };

    // Primary geolocation service (single provider approach)
    const PRIMARY_SERVICE = {
        url: 'https://ip-api.com/json/?fields=status,countryCode',
        extractCountry: (data) => data.status === 'success' ? data.countryCode : null,
        name: 'IP-API'
    };

    // Country to language mapping for timezone-based detection
    const TIMEZONE_TO_COUNTRY = {
        'Europe/Berlin': 'DE',
        'Europe/Vienna': 'DE',
        'Europe/Zurich': 'DE',
        'America/New_York': 'US',
        'America/Los_Angeles': 'US',
        'America/Chicago': 'US',
        'Europe/London': 'GB',
        'Europe/Paris': 'FR',
        'Europe/Madrid': 'ES',
        'Europe/Rome': 'IT',
        'Asia/Tokyo': 'JP',
        'Asia/Shanghai': 'CN',
        'Australia/Sydney': 'AU'
    };

    class AIWGeo {
        constructor(config = {}) {
            this.config = { ...DEFAULT_CONFIG, ...config };
            this.debugLog = this.config.debugMode ? console.log.bind(console, '[AIWGeo]') : () => {};
            this.errorLog = this.config.silentErrors && !this.config.debugMode ? 
                () => {} : console.error.bind(console, '[AIWGeo Error]');
        }

        /**
         * Main public method: Get country code with caching and privacy controls
         * @param {Object} options - Override options for this request
         * @returns {Promise<string|null>} Country code (ISO 3166-1 alpha-2) or null
         */
        async getCountry(options = {}) {
            const requestConfig = { ...this.config, ...options };
            
            this.debugLog('Getting country with config:', requestConfig);

            // Check if geolocation is disabled
            if (!requestConfig.enabled) {
                this.debugLog('Geolocation disabled by configuration');
                return null;
            }

            // Check privacy consent if required
            if (requestConfig.privacy.requireConsent && !this.hasConsent()) {
                this.debugLog('Geolocation consent not granted');
                return null;
            }

            // Try server-provided country first
            if (requestConfig.serverCountry) {
                this.debugLog('Using server-provided country:', requestConfig.serverCountry);
                return requestConfig.serverCountry.toUpperCase();
            }

            // Try cached country
            if (requestConfig.useCache) {
                const cachedCountry = this.getCachedCountry();
                if (cachedCountry) {
                    this.debugLog('Using cached country:', cachedCountry);
                    return cachedCountry;
                }
            }

            // Attempt network-based detection
            try {
                const networkCountry = await this.detectCountryFromNetwork(requestConfig);
                if (networkCountry) {
                    // Cache the result
                    if (requestConfig.useCache) {
                        this.setCachedCountry(networkCountry);
                    }
                    this.debugLog('Successfully detected country from network:', networkCountry);
                    return networkCountry;
                }
            } catch (error) {
                this.errorLog('Network country detection failed:', error.message);
            }

            // Fallback to timezone-based detection
            if (requestConfig.fallbackToTimezone) {
                const timezoneCountry = this.detectCountryFromTimezone();
                if (timezoneCountry) {
                    this.debugLog('Using timezone-based country:', timezoneCountry);
                    return timezoneCountry;
                }
            }

            this.debugLog('All country detection methods failed');
            return null;
        }

        /**
         * Detect country from network (single provider approach)
         */
        async detectCountryFromNetwork(config) {
            this.debugLog('Attempting network-based country detection...');

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), config.networkTimeoutMs);

            try {
                const response = await fetch(PRIMARY_SERVICE.url, {
                    method: 'GET',
                    signal: controller.signal,
                    headers: {
                        'Accept': 'application/json'
                    },
                    mode: 'cors' // Explicit CORS mode
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                const country = PRIMARY_SERVICE.extractCountry(data);

                if (country && typeof country === 'string' && /^[A-Z]{2}$/.test(country)) {
                    return country;
                }

                throw new Error('Invalid country code format received');

            } catch (error) {
                clearTimeout(timeoutId);
                
                // Handle specific error types quietly
                if (error.name === 'AbortError') {
                    throw new Error('Network timeout');
                } else if (error.name === 'TypeError' && error.message.includes('CORS')) {
                    throw new Error('CORS policy blocked request');
                } else if (error.name === 'TypeError') {
                    throw new Error('Network connectivity issue');
                } else {
                    throw error;
                }
            }
        }

        /**
         * Detect country from browser timezone (privacy-friendly fallback)
         */
        detectCountryFromTimezone() {
            try {
                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                this.debugLog('Detected timezone:', timezone);
                
                return TIMEZONE_TO_COUNTRY[timezone] || null;
            } catch (error) {
                this.debugLog('Timezone detection failed:', error.message);
                return null;
            }
        }

        /**
         * Cache management methods
         */
        getCachedCountry() {
            try {
                const timestamp = localStorage.getItem(CACHE_KEYS.timestamp);
                const country = localStorage.getItem(CACHE_KEYS.country);

                if (!timestamp || !country) {
                    return null;
                }

                const age = Date.now() - parseInt(timestamp, 10);
                if (age > this.config.cacheTimeoutMs) {
                    this.clearCache();
                    return null;
                }

                return country;
            } catch (error) {
                this.debugLog('Cache read failed:', error.message);
                return null;
            }
        }

        setCachedCountry(country) {
            try {
                localStorage.setItem(CACHE_KEYS.country, country);
                localStorage.setItem(CACHE_KEYS.timestamp, Date.now().toString());
            } catch (error) {
                this.debugLog('Cache write failed:', error.message);
            }
        }

        clearCache() {
            try {
                localStorage.removeItem(CACHE_KEYS.country);
                localStorage.removeItem(CACHE_KEYS.timestamp);
            } catch (error) {
                this.debugLog('Cache clear failed:', error.message);
            }
        }

        /**
         * Privacy consent management
         */
        hasConsent() {
            try {
                return localStorage.getItem(CACHE_KEYS.consent) === 'true';
            } catch (error) {
                return false;
            }
        }

        grantConsent() {
            try {
                localStorage.setItem(CACHE_KEYS.consent, 'true');
                return true;
            } catch (error) {
                this.debugLog('Failed to save consent:', error.message);
                return false;
            }
        }

        revokeConsent() {
            try {
                localStorage.removeItem(CACHE_KEYS.consent);
                this.clearCache(); // Clear any cached location data
                return true;
            } catch (error) {
                this.debugLog('Failed to revoke consent:', error.message);
                return false;
            }
        }

        /**
         * Utility methods
         */
        isEnabled() {
            return this.config.enabled;
        }

        getConfig() {
            return { ...this.config };
        }

        // Method to update configuration
        updateConfig(newConfig) {
            this.config = { ...this.config, ...newConfig };
            this.debugLog = this.config.debugMode ? console.log.bind(console, '[AIWGeo]') : () => {};
            this.errorLog = this.config.silentErrors && !this.config.debugMode ? 
                () => {} : console.error.bind(console, '[AIWGeo Error]');
        }
    }

    // Create and export singleton instance
    const aiwGeo = new AIWGeo();

    // Export to global scope
    if (typeof window !== 'undefined') {
        window.AIWGeo = aiwGeo;
    }

    // Also support module exports for testing
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { AIWGeo: AIWGeo, aiwGeo: aiwGeo };
    }

})(typeof window !== 'undefined' ? window : global);