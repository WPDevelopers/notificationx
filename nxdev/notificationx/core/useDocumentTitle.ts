const useDocumentTitle = ( {title, ...rest} ) => {
    const documentTitle = document.querySelector('title');
    documentTitle.innerHTML = title;
}

export default useDocumentTitle;