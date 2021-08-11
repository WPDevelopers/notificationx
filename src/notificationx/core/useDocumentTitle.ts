import { useEffect } from "@wordpress/element";
import { addFilter } from '@wordpress/hooks'

const useDocumentTitle = ( {title, ...rest} ) => {
    const documentTitle = document.querySelector('title');
    documentTitle.innerHTML = title;
}

export default useDocumentTitle;