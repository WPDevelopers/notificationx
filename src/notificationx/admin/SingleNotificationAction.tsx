import React, { useCallback, useState } from 'react'
import { __ } from '@wordpress/i18n';
import { Link, Redirect } from 'react-router-dom';
import nxHelper from '../core/functions';
import { useBuilderContext } from '../../form-builder';
import { CopyToClipboard } from 'react-copy-to-clipboard';
import { SweetAlert } from "../../form-builder/src/core/functions";
import { useNotificationXContext } from '../hooks';

const SingleNotificationAction = ({
    id,
    getNotice,
    updateNotice,
    regenerate,
}) => {
    const builderContext = useNotificationXContext();
    const [redirect, setRedirect] = useState<string>()

    const handleDelete = useCallback(
        (event) => {
            if (id) {
                nxHelper.swal({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete It',
                    cancelButtonText: 'No, Cancel',
                    reverseButtons: true,
                    customClass: { actions: 'nx-delete-actions' },
                    confirmedCallback: () => {
                        return nxHelper.delete(`nx/${id}`, { nx_id: id });
                    },
                    completeAction: (response) => {
                        updateNotice(notices => notices.filter(
                            (notice) => parseInt(notice.nx_id) !== parseInt(id)
                        ));
                    },
                    completeArgs: {
                        title: 'Complete',
                        text: "Deleted!",
                        icon: 'success',
                        timer: 1500,
                    },
                    afterComplete: () => {
                        setRedirect('/');
                    }

                });
            }
        },
        [id, getNotice]
    );

    const handleRegenerate = (event) => {
        nxHelper.swal({
            title: 'Are you sure?',
            text: "Regenerate",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Regenerate',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: { actions: 'nx-delete-actions' },
            confirmedCallback: () => {
                return nxHelper.get(`regenerate/${id}`, { nx_id: id });
            },
            completeAction: (response) => {

            },
            completeArgs: {
                title: 'Regenerated',
                text: "Successfully Regenerated!",
                icon: 'success',
                timer: 2000,
            },
            afterComplete: () => {
                setRedirect('/');
            }

        });
    };

    const onCopy = () => SweetAlert({
        showConfirmButton: false,
        type: 'success',
        timer: 1500,
        title: 'Copied to clipboard',
        text: '',
        html: `[notificationx id=${id}]`,
    });

    return (
        <div className="nx-admin-actions">
            {
                redirect && <Redirect to={redirect} />
            }
            <Link className="nx-admin-title-edit" title="Edit" to={`/edit/${id}`}><span>{__('Edit', 'notificationx')}</span></Link>
            <Link className="nx-admin-title-duplicate" title="Duplicate" to={{
                pathname: '/add-new',
                state: { duplicate: true, _id: id }
            }}><span>{__('Duplicate', 'notificationx')}</span></Link>
            {
                builderContext?.is_pro_active &&
                <CopyToClipboard className="nx-admin-title-shortcode nx-shortcode-btn" title="Shortcode" text={`[notificationx id=${id}]`} onCopy={onCopy} >
                    <a></a>
                </CopyToClipboard>
            }
            {/* <Link className="nx-admin-title-duplicate" title="Entries" to={`/entries/${id}`}><span>{__('Entries', 'notificationx')}</span></Link> */}
            {regenerate && (
                <button
                    className="nx-admin-title-regenerate"
                    onClick={handleRegenerate}
                    title="Re Generate"
                >
                    <span>{__("Re Generate", "notificationx")}</span>
                </button>
            )}
            <button className="nx-admin-title-trash" title="Delete" onClick={handleDelete}>
                <span>{__("Delete", "notificationx")}</span>
            </button>
        </div>
    );
};

export default SingleNotificationAction;
