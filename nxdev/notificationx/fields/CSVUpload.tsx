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
    const [csvData, setCSVData] = useState(null)
    const builderContext = useBuilderContext();
    const nxContext = useNotificationXContext();
    const [importCSV, setImportCSV] = useState(false);
    const [complete, setComplete] = useState(false);
    const [localContents, setLocalContents] = useState([]);
    const [progress, setProgress] = useState(0);
    const csv_upload_loader = nxContext?.state?.csv_upload_loader?.csv_upload_loader;


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
                confirmButtonText: __("Close", "notificationx"),
                customClass: {
                    container: 'nx-csv-modal-ift-container',
                    popup: 'nx-csv-modal-ift-popup',
                    actions: "nx-delete-actions nx-csv-invalid-file-type",
                  },
                confirmedCallback: () => {},
                completeAction: (response) => {},
                completeArgs: () => {},
                afterComplete: () => { },
            });
            return;
        }
        try {
            const itemCount = await checkCSVItems(media.url);
            if (itemCount > parseInt( nxContext?.cus_imp_limit ) ) {
                Swal.fire({
                    title: __("Import Limit Exceeded.", "notificationx"),
                    html: __(
                        `Your file contains more than ${parseInt( nxContext?.cus_imp_limit )} rows. Only the first ${parseInt( nxContext?.cus_imp_limit )} rows will be imported. Click <strong>"Continue"</strong> to proceed or <strong>"Cancel"</strong> to abort.`,
                        "notificationx"
                    ),
                    iconHtml: `<img alt="NotificationX" src="${builderContext.assets.admin}images/file-type.svg" style="height: 85px; width:85px" />`,
                    showDenyButton: true,
                    iconColor: "transparent",
                    confirmButtonText: __("Continue", "notificationx"),
                    denyButtonText: __("Cancel", "notificationx"),
                    reverseButtons: true,
                    customClass: {
                        container: 'nx-csv-modal-import-limit-container',
                        popup: 'nx-csv-modal-import-limit-popup',
                        actions: "nx-delete-actions nx-csv-import-limit",
                      },
                    allowOutsideClick: false,
                    // @ts-ignore 
                }).then((result) => {
                    if (result.isConfirmed) {
                        setCSVData({
                            id: media.id,
                            title: media?.filename,
                            url: media.url,
                        });
                        setImportCSV(true);
                    } else if (result.isDenied) {
                        setCSVData(null);
                        setImportCSV(false);
                    }
                });
            } else {
                setCSVData({
                    id: media.id,
                    title: media?.filename,
                    url: media.url
                });
                setImportCSV(true);
            }
        } catch (error) {
            console.error("Error processing the CSV file:", error);
            nxToast.error(__('Error processing the CSV file', 'notificationx'));
        }
    };
    
    useEffect(() => {
        if( importCSV ) {
          importCSVData();
        }      
      }, [importCSV])  
    
      const generateChunkSize = (csvLength) => {
        let chunkSize;
    
        if (csvLength > 1000) {
            chunkSize = 100; // Fixed chunk size if csvLength is more than 1000
        } else {
            // Calculate chunk size based on a maximum of 10 requests
            chunkSize = Math.ceil(csvLength / 10);
        }
    
        return chunkSize;
    };

    const importCSVData = async () => {
        const csvUrl      = csvData?.url;
        const csvContent  = await fetch(csvUrl).then(res => res.text());
        const lines       = csvContent.split('\n');
        const chunkSize   = generateChunkSize( lines?.length );
        const totalChunks = Math.ceil(lines.length / chunkSize);

        for (let i = 0; i < totalChunks; i++) {    
            await uploadChunk(csvUrl, chunkSize, i, totalChunks, csvData?.id);
            const progressValue = Math.round(((i + 1) / totalChunks) * 100);
            setProgress(progressValue);            
            // @ts-ignore 
            const progressBar = document.getElementById('nx-progress-bar');
            if (progressBar) {
                progressBar.style.width = `${progressValue}%`;
                progressBar.querySelector('span').innerText = `${progressValue}%`;
            }
        }

        setComplete(true);
        Swal.close();
        nxToast.info(__('CSV data imported successfully!', "notificationx"));
    }

    const uploadChunk = async (csvUrl, chunkSize, chunkIndex, totalChunks, mediaId) => {
        nxContext.setCSVUploaderLoader({ csv_upload_loader: true });
        try {
            const response = await nxHelper.post("csv-upload", {
                csv: csvUrl,
                chunkIndex,
                totalChunks,
                mediaId,
                chunkSize,
                uploadImage: true,
            });
            // @ts-ignore 
            if (response.success) {
                setLocalContents(prevContents => [
                    ...prevContents,
                    // @ts-ignore 
                    ...(response.data.data || [])
                ]);
            } else {
                // @ts-ignore 
                console.error(response.data.error);
                nxContext.setCSVUploaderLoader({
                    csv_upload_loader: false,
                })
            }
        } catch (error) {
            console.error("Error uploading the chunk:", error);
        } finally {
            nxContext.setCSVUploaderLoader({ csv_upload_loader: false });
        }
    };

    useEffect(() => {
        if (complete) {
            builderContext.setFieldValue("custom_contents", localContents);
        }
    }, [complete]);

    return (
        <div className="wprf-control wprf-media wprf-csv-upload">
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
            { (progress > 0 || csv_upload_loader) &&
                <div className="progress-container">
                    <div id="nx-progress-bar" className="nx-progress-bar"><span>0%</span></div>
                </div>
            }
        </div>
    )
}

export default withLabel(Media);