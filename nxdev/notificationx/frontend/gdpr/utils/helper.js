export const thirdPartyAnalytics = [
    {
        name: 'Google Analytics',
        scriptUrl: 'https://www.googletagmanager.com/gtag/js?id=YOUR_TRACKING_ID',
        load: () => {
            if (!window.gtag) {
                window.dataLayer = window.dataLayer || [];
                function gtag() {
                    window.dataLayer.push(arguments);
                }
                window.gtag = gtag;

                gtag('js', new Date());
                gtag('config', 'YOUR_TRACKING_ID', { anonymize_ip: true });
            }
        },
        disable: () => {
            window[`ga-disable-YOUR_TRACKING_ID`] = true;
            document.cookie = `_ga=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
            document.cookie = `_gid=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        },
    },
    {
        name: 'Facebook Pixel',
        scriptUrl: 'https://connect.facebook.net/en_US/fbevents.js',
        load: () => {
            if (!window.fbq) {
                !function(f,b,e,v,n,t,s)
                {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)}(window,document,'script',
                'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', 'YOUR_PIXEL_ID');
                fbq('track', 'PageView');
            }
        },
        disable: () => {
            document.cookie = `_fbp=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        },
    },
];

export const isValidScriptContent = (scriptContent) => {
    try {
        const unsafePatterns = [/<script>/gi, /<\/script>/gi];
        return !unsafePatterns.some(pattern => pattern.test(scriptContent));
    } catch (error) {
        return false;
    }
};
export const processScriptContent = (scriptContent) => {
    const scriptTagMatch = scriptContent.match(/<script[^>]*>([\s\S]*?)<\/script>/i);
    if (scriptTagMatch) {
        return scriptTagMatch[1];
    }
    return scriptContent;
};

export const setDynamicCookie = (type, value, COOKIE_EXPIRY_DAYS) => {
    const cookieName = 'nx_cookie_manager';
    const expires = new Date(Date.now() + COOKIE_EXPIRY_DAYS * 24 * 60 * 60 * 1000).toUTCString();

    // Retrieve the existing cookie manager object
    const existingCookie = document.cookie
        .split('; ')
        .find((row) => row.startsWith(`${cookieName}=`));
    const cookieManager = existingCookie
        ? JSON.parse(decodeURIComponent(existingCookie.split('=')[1]))
        : {};

    // Update the specific consent type
    cookieManager[type] = value;

    // Save the updated cookie manager object back as a single cookie
    document.cookie = `${cookieName}=${encodeURIComponent(
        JSON.stringify(cookieManager)
    )}; expires=${expires}; path=/;`;
};


export const getDynamicCookie = (type) => {
    const cookieName = 'nx_cookie_manager';
    const existingCookie = document.cookie
        .split('; ')
        .find((row) => row.startsWith(`${cookieName}=`));

    if (!existingCookie) return null;

    const cookieManager = JSON.parse(decodeURIComponent(existingCookie.split('=')[1]));
    return cookieManager[type] ?? null; // Return the value of the specified type or null if not found
};


export const loadScripts = (cookieList) => {
    if (!cookieList || cookieList.length < 0) return;

    cookieList.forEach(cookie => {
        if (cookie?.script_url_pattern) {
            const scriptsContent = cookie.script_url_pattern;

            // Create a temporary container to parse the <script> tags
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = scriptsContent; // Place the script content in a div to parse it as HTML

            // Loop through all <script> tags in the parsed content
            const scriptTags = tempDiv.getElementsByTagName('script');

            Array.from(scriptTags).forEach(scriptTag => {
                const scriptElement = document.createElement('script');
                scriptElement.type = 'text/javascript';

                // If the script has a 'src' attribute, it's an external script
                if (scriptTag.src) {
                    scriptElement.src = scriptTag.src;
                    scriptElement.async = true;  // Load the external script asynchronously
                } else {
                    // If it's inline script, take the text content
                    scriptElement.textContent = scriptTag.textContent;
                }

                // Append the script tag to either <head> or <body> based on `load_inside`
                switch (cookie?.load_inside) {
                    case 'head':
                        document.head.appendChild(scriptElement);
                        break;
                    case 'body':
                        document.body.appendChild(scriptElement);
                        break;
                    case 'footer':
                        const footer = document.querySelector('footer');
                        if (footer) {
                            footer.appendChild(scriptElement);
                        } else {
                            document.body.appendChild(scriptElement);
                        }
                        break;
                    default:
                        document.head.appendChild(scriptElement);
                        break;
                }
            });
        }
    });
};

export const cookieCategoryPrefix = {
    necessary: [
      'PHPSESSID', 'wordpress_logged_in', 'wp-settings', 'wp-settings-time',
      'wpEmojiSettingsSupports', 'cookieyes-consent', 'elementor', 'csrftoken',
      'auth', 'session', 'secure', 'cart', 'checkout', 'wp_woocommerce'
    ],
    functional: [
      'lang', 'preferences', 'remember_me', 'theme', 'consent', 'locale',
      'user_settings', 'cookie_preference'
    ],
    analytics: [
      '_ga', '_gid', '_gat', 'fbp', 'utm', 'amplitude', 'mixpanel', 'hotjar',
      'segment', 'ahoy', 'kissmetrics', 'analytics', 'visitor_id',
      'sbjs_udata', 'sbjs_current', 'sbjs_first', 'sbjs_first_add', 'sbjs_current_add'
    ],
    performance: [
      '_hj', 'cf_use_ob', 'cf_clearance', 'AWSALB', 'load_balancer',
      'page_speed', 'cdn_cache', 'pingdom', 'new_relic'
    ],
    advertisement: [
      'ads', '_fbp', '_gcl', '_dc_gtm', 'doubleclick', 'IDE', 'adroll',
      'criteo', 'twitter_ads', 'bing_ads', 'remarketing', 'test_cookie'
    ]
};

// Function to add class to span inside specific data-key divs
export const addCookiesAddedClass = (dataKeys) => {
    dataKeys.forEach(key => {
        const div = document.querySelector(`[data-key="${key}_tab"]`);
        if (div) {
            const listCountSpan = div.querySelector('.list-count');
            if (listCountSpan) {
                listCountSpan.classList.add('cookies-added');
            }
        }
    });
}

export const formatDateTime = (dateString) => {
    // Convert the date string to a Date object by replacing space with 'T' (ISO 8601 format)
    const date = new Date(dateString.replace(' ', 'T'));
  
    // Format the date to a human-readable format
    return date.toLocaleString('en-US', {
      year  : 'numeric',
      month : 'long',
      day   : 'numeric',
      hour  : 'numeric',
      minute: 'numeric',
      hour12: true
    });
  }