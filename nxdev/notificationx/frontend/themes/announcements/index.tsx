import React, { Suspense, lazy, useMemo } from 'react'

const Announcements = ({themeName, data, config, id, style, componentClasses}) => {
    const Svg = useMemo(() => {
        switch (themeName) {
            case 'theme-1':
                return lazy(() => import('./theme-1'));
                break;

            case 'theme-2':
                return lazy(() => import('./theme-2'));
                break;

            case 'theme-12':
                return lazy(() => import('./theme-1'));
                break;

            default:
                break;
        }
    }, [themeName]);


    // console.log(themeName, data);

    return (
        <div
            className={componentClasses}
            {...data?.image_data?.attr}
            style={style}
        >
            <Suspense fallback={<></>} >
                <Svg {...data} />
            </Suspense>
        </div>
    );
}

export default Announcements
