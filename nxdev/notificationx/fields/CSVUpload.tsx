import React, { useState, useEffect, useMemo } from 'react'
import { MediaUpload } from '@wordpress/media-utils';
import { useBuilderContext, withLabel } from 'quickbuilder';
import nxHelper, { checkCSVItems } from '../core/functions';
import { __, sprintf } from '@wordpress/i18n';
import DownloadIcon from '../icons/DownloadIcon';
import UploadIcon from '../icons/UploadIcon';
import Ic_Round_Done from '../icons/check_done';
import FileIcon from '../icons/FileIcon';
import Swal from 'sweetalert2';
import nxToast from '../core/ToasterMsg';
import { useNotificationXContext } from '../hooks';


const Media = (props) => {
    const [csvData, setCSVData] = useState(props.value?.url ? props.value : null)
    const builderContext = useBuilderContext();
    const nxContext = useNotificationXContext();
    const [importBtnClass, setImportButtonClass] = useState('wprf-btn wprf-import-csv-btn');
    const [loading, setLoading]  = useState(false);
    const [complete, setComplete] = useState(false);

    useEffect(() => {
        if (csvData) {
            props.onChange({
                target: {
                    type: 'media',
                    name: props.name,
                    value: csvData
                }
            })
        }
    }, [csvData])


    const handleMediaSelection = async (media) => {
        if (media.mime !== 'text/csv') {
            nxHelper.swal({
                title: __("Invalid File Type!", "notificationx"),
                text: __(
                    "Please upload a CSV file to import custom notification data.",
                    "notificationx"
                ),
                iconHtml: `<img alt="NotificationX" src="${builderContext.assets.admin}images/file-type.svg" />`,
                confirmButtonText: __("Cancel", "notificationx"),
                customClass: { actions: "nx-delete-actions" },
                confirmedCallback: () => {},
                completeAction: (response) => {},
                completeArgs: () => {},
                afterComplete: () => { },
            });
            return;
        }
        try {
            const itemCount = await checkCSVItems(media.url);
            if (itemCount > 101) {
                Swal.fire({
                    title: __("Import Limit Exceeded.", "notificationx"),
                    html: __(
                        "Your file contains more than 100 rows. Only the first 100 rows will be imported. Click <strong>Continue</strong> to proceed or <strong>Cancel</strong> to abort.",
                        "notificationx"
                    ),
                    iconHtml: `<img alt="NotificationX" src="${builderContext.assets.admin}images/file-type.svg" style="height: 85px; width:85px" />`,
                    showDenyButton: true,
                    iconColor: "transparent",
                    confirmButtonText: __("Add First 100 Entries", "notificationx"),
                    denyButtonText: __("Cancel", "notificationx"),
                    reverseButtons: true,
                    customClass: { actions: "nx-delete-actions" },
                    allowOutsideClick: false,
                    // @ts-ignore 
                }).then((result) => {
                    if (result.isConfirmed) {
                        setCSVData({
                            id: media.id,
                            title: media.title,
                            url: media.url
                        });
                    } else if (result.isDenied) {
                        setCSVData(null);
                    }
                });
            } else {
                setCSVData({
                    id: media.id,
                    title: media.title,
                    url: media.url
                });
            }
        } catch (error) {
            console.error("Error processing the CSV file:", error);
        }
    }

    const importCSVData = () => {
        setImportButtonClass('wprf-btn wprf-import-csv-btn loading');
        nxContext.setCSVUploaderLoader({
            csv_upload_loader: true,
        })
        nxHelper.post("csv-upload", {
            csv: csvData,
            uploadImage: true,
            take: 100,
        }).then((res: any) => {
            builderContext.setFieldValue(
                "custom_contents",
                res.data.data
            )
            setImportButtonClass('wprf-btn wprf-import-csv-btn completed');
            setLoading(false);
            setComplete(true);
            nxToast.info(
                __(
                    `CSV data imported successfully!`,
                    "notificationx"
                )
            );
            nxContext.setCSVUploaderLoader({
                csv_upload_loader: false,
            })
        }).catch((error) => {
            setImportButtonClass('wprf-btn wprf-import-csv-btn error');
            setLoading(false);
            console.error(error);
            nxContext.setCSVUploaderLoader({
                csv_upload_loader: false,
            })
        });
    }

    return (
        <div className="wprf-control wprf-media">
            <div className="wprf-image-uploader wprf-csv-uploader">
                <MediaUpload
                    onSelect={(media) => handleMediaSelection(media)}
                    multiple={false}
                    value={csvData}
                    render={({ open }) => {
                        return <>
                            <button
                                className="wprf-btn wprf-csv-upload-btn"
                                onClick={open}
                            >
                                { complete ? <Ic_Round_Done /> : <UploadIcon /> }  { csvData != null ? (props?.reset || __('Upload', 'notificationx')) : (props?.button || 'Upload') }
                            </button>
                            {csvData?.title && <span>{csvData?.title}</span>}
                            {/* disabled={totalAddedItems?.length >= 100 ? true : false} */}
                            <button className={importBtnClass} disabled={ csvData == null ? true : false } onClick={() => importCSVData()}>
                                <DownloadIcon /> {'Import'}
                            </button>
                            <a
                                className='wprf-btn wprf-btn-sample-csv'
                                href={`${nxContext.assets.admin}sample_data.csv`}
                                download
                            >
                                <FileIcon/> {__('Sample CSV', 'notificationx')}
                            </a>
                        </>
                    }}
                />

            </div>
        </div>
    )
}

export default withLabel(Media);