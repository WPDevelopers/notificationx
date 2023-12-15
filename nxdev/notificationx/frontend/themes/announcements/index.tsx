import React, { Suspense, lazy, useMemo } from 'react'

const Announcements = ({themeName, data, config, id, style, componentClasses}) => {
    const Svg = useMemo(() => {
        switch (themeName) {
            case 'theme-1':
                return lazy(() => import('./theme-1'));
            case 'theme-2':
                return lazy(() => import('./theme-2'));
            case 'theme-12':
                return lazy(() => import('./theme-1'));
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
