const notificationXReducer = (state: any, action: any) => {
    switch (action.type) {
        case 'SET_TIME_SETTINGS':
            return {...state, settings: { ...state.settings, time: action.payload }};
        case 'SET_COMMON_OPTIONS':
            return {...state, common: { ...state.common, [action.payload.field]: action.payload.value }};
        case 'SET_REDIRECT':
            return { ...state, redirect: { ...state.redirect, ...action.payload } }
        case 'SET_RESET':
            return { ...state, analytics: { ...state.analytics, ...action.payload } }
    }
}

export default notificationXReducer;