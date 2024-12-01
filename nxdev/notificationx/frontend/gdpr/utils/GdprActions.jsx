import React, { useState } from 'react'
import ReactModal from "react-modal";
import { modalStyle } from '../../../core/constants';

const GdprActions = ({ settings }) => {
    const [isOpenCustomizationModal, setIsOpenGdprCustomizationModal] = useState(false);

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
                className='nx-get-started-modal'
                style={modalStyle}
            >
                <>
                    <h2>Hello World!!</h2>
                </>
            </ReactModal>
    </div>
  )
}

export default GdprActions