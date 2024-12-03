import React, { useState } from 'react'
import ReactModal from "react-modal";
import { modalStyle } from '../../../core/constants';
import { __ } from '@wordpress/i18n';
import Customization from '../Customization';
import CloseIcon from '../../../icons/Close';

const GdprActions = ({ settings }) => {
    const [isOpenCustomizationModal, setIsOpenGdprCustomizationModal] = useState(false);
    const [enabledItem, setEnabledItem] = useState([]);
    console.log('enabledItem',enabledItem);
    return (
        <div className="nx-gdpr-actions">
            <div className="button-group">
                <button type="button" className="btn btn-primary">{settings?.gdpr_accept_btn}</button>
                <button type="button" onClick={ () => setIsOpenGdprCustomizationModal(!isOpenCustomizationModal) } className="btn btn-secondary">{ settings?.gdpr_customize_btn }</button>
            </div>
            <div className="button-single">
                <button type="button" className="btn btn-danger">{settings?.gdpr_reject_btn}</button>
            </div>
            <ReactModal
                isOpen={isOpenCustomizationModal}
                onRequestClose={() => setIsOpenGdprCustomizationModal(false)}
                className={`nx-gdpr-customization`}
                style={modalStyle}
                ariaHideApp={false}
            >
                <Customization settings={settings} onEnableCookiesItem={setEnabledItem}/>
                <button type="button" onClick={ () => setIsOpenGdprCustomizationModal(false) } className="nx-gdpr-customization-close" aria-label="Close">
                    <CloseIcon/>
                </button>
            </ReactModal>
        </div>
    )
}

export default GdprActions