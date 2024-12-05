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
