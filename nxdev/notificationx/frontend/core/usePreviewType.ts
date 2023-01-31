import { useEffect, useState } from 'react'


const usePreviewType = (): String => {
  const [previewType, setPreviewType] = useState<String>(window.location.hash.replace('#', ''));

  useEffect(() => {

    const setWindowLocation = () => {
      const _previewType = window.location.hash.replace('#', '');
      setPreviewType(_previewType);
    }

    if (!previewType) {
      setWindowLocation()
    }

    window.addEventListener('popstate', setWindowLocation)

    return () => {
      window.removeEventListener('popstate', setWindowLocation)
    }
  }, [previewType]);

  return previewType
}

export default usePreviewType;