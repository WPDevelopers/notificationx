const frontendReducer = (state: any, action: any) => {
    switch (action.type) {
        case "ADD_NOTIFICATION":
            let updatedState = { ...state, notices: [ ...state.notices, action.payload ] };
            return updatedState;
        case "REMOVE_NOTIFICATION":
            return {
                ...state,
                notices: (state.notices.length > 0 && state.notices.filter( notice => notice.id !== action.payload )) || state.notices || []
            };
        case "ADD_TEMPLATES":
            return { ...state, templates: action.payload };
        default:
            return state;
    }
}

export default frontendReducer;