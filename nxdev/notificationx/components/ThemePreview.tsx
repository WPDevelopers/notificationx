import React, { useEffect, useState } from 'react'
import { useBuilderContext } from '../../form-builder'

const ThemePreview = ({ name, preview }) => {

    return (
        <>
            {
                preview &&
                <div className="nx-admin-preview">
                    <img src={preview} alt={name} />
                </div>
            }
        </>
    )
}

export default React.memo(ThemePreview);
