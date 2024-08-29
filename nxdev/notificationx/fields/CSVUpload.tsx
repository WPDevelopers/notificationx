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
    const [complete, setComplete] = useState(false);
    const [localContents, setLocalContents] = useState([]);
    const [progress, setProgress] = useState(0);

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
            return;
        }
        try {
            setCSVData({
                id: media.id,
                title: media?.filename,
                url: media.url,
            });
            const csvUrl = media.url.replace('http://', 'https://');
            const csvContent = await fetch(csvUrl).then(res => res.text());
            const lines = csvContent.split('\n');
            const chunkSize = 10;
            const totalChunks = Math.ceil(lines.length / chunkSize);
            
            Swal.fire({
                title: __('Uploading CSV...', 'notificationx'),
                html: `<progress id="csv-progress-bar" value="0" max="100" style="width: 100%"></progress>`,
                showConfirmButton: false,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
    
            for (let i = 0; i < totalChunks; i++) {    
                await uploadChunk(csvUrl, chunkSize, i, totalChunks, media.id);
                const progressValue = Math.round(((i + 1) / totalChunks) * 100);
                setProgress(progressValue);
                // @ts-ignore 
                document.getElementById('csv-progress-bar').value = progressValue;
            }
    
            setComplete(true);
            Swal.close();
            nxToast.info(__('CSV data imported successfully!', "notificationx"));
        } catch (error) {
            console.error("Error processing the CSV file:", error);
            Swal.close();
            nxToast.error(__('Error processing the CSV file', 'notificationx'));
        }
    };
    
    
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