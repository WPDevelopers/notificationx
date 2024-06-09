import React, { useState, useEffect, useMemo } from 'react'
import { MediaUpload } from '@wordpress/media-utils';
import { useBuilderContext, withLabel } from 'quickbuilder';
import nxHelper, { chunkArray } from '../core/functions';
import ReactModal from "react-modal";
import { __ } from '@wordpress/i18n';
import DownloadIcon from '../icons/DownloadIcon';
import UploadIcon from '../icons/UploadIcon';
import Ic_Round_Done from '../icons/check_done';
import Pagination from 'rc-pagination';
import { SelectControl } from "@wordpress/components";
import Swal from 'sweetalert2';


const Media = (props) => {
    const [csvData, setCSVData] = useState(props.value?.url ? props.value : null)
    const builderContext = useBuilderContext();
    const [isOpen, setIsOpen] = useState(false);
    const [headers, setHeaders] = useState([]);
    const [importBtnClass, setImportButtonClass] = useState('wprf-btn wprf-import-csv-btn');
    const [loading, setLoading]  = useState(false);
    const [complete, setComplete] = useState(false);
    const [data, setData] = useState([]);
    const [itemsPerPage, setItemsPerPage] = useState(5);
    const [currentPage, setCurrentPage] = useState(1);


    useEffect(() => {
        if (csvData) {
            props.onChange({
                target: {
                    type: 'media',
                    name: props.name,
                    value: csvData
                }
            })
            nxHelper.post("csv-upload", {
                csv: csvData,
                uploadImage: false
            }).then((res: any) => {
                if (res.data.data.length > 100) {
                    // @ts-ignore 
                    Swal.fire({
                        title: __("Your CSV file contains more than 100 data entries.", "notificationx"),
                        text: __(
                            "We can only add 100 entries at a time. To proceed, you can either add the first 100 entries or cancel the operation.",
                            "notificationx"
                        ),
                        iconHtml: `<img alt="NotificationX" src="${builderContext.assets.admin}images/regenerate.svg" style="height: 85px; width:85px" />`,
                        showDenyButton: true,
                        iconColor: "transparent",
                        confirmButtonText: __("Add First 100 Entries", "notificationx"),
                        denyButtonText: __("Cancel", "notificationx"),
                        reverseButtons: true,
                        customClass: { actions: "nx-delete-actions" },
                        // @ts-ignore 
                    }).then((result) => {
                        if (result.isConfirmed) {
                            setHeaders(res.data.headers);
                            setData(chunkArray(res.data.data, 5));
                        } else if (result.isDenied) {
                            setData([]);
                            setCSVData(null);
                        }
                    });
                }
            }).catch((error) => {
                setImportButtonClass('wprf-btn wprf-import-csv-btn error');
            });
        }
    }, [csvData])

    const importCSVData = () => {
        setImportButtonClass('wprf-btn wprf-import-csv-btn loading');
        setLoading(true);
        nxHelper.post("csv-upload", {
            csv: csvData,
            uploadImage: true,
            take: 100,
        }).then((res: any) => {
            setHeaders(res.data.headers);
            setData(res.data.data);
            builderContext.setFieldValue(
                "custom_contents",
                res.data.data
            )
            setImportButtonClass('wprf-btn wprf-import-csv-btn completed');
            setLoading(false);
            setComplete(true);
        }).catch((error) => {
            setImportButtonClass('wprf-btn wprf-import-csv-btn error');
            setLoading(false);
        });
    }

    const previewCSVData = () => {
        setIsOpen(true);
    }

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };

    const csvLocalMemoizedValue = useMemo(() => {
        return data.flat();
    }, [data]);

    const paginatedData = useMemo(() => {
        const startIndex = (currentPage - 1) * itemsPerPage;
        return csvLocalMemoizedValue.slice(startIndex, startIndex + itemsPerPage);
    }, [currentPage, csvLocalMemoizedValue, itemsPerPage]);

    const handleItemsPerPageChange = (value) => {
        setItemsPerPage(parseInt(value));
        setCurrentPage(1);
    };

    const totalItems = csvLocalMemoizedValue?.length || 0;
    const startIndex = (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, totalItems);
    const totalAddedItems = builderContext.getFieldValue('custom_contents');

    return (
        <div className="wprf-control wprf-media">
            <div className="wprf-image-uploader wprf-csv-uploader">
                <MediaUpload
                    onSelect={(media) => {
                        setCSVData({
                            id: media.id,
                            title: media.title,
                            url: media.url
                        });
                    }}
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
                            <button
                                className='wprf-btn wprf-btn-sample-csv'
                            >
                                {__('Sample CSV', 'notificationx')}
                            </button>
                        </>
                    }}
                />
            </div>
            <ReactModal
                isOpen={isOpen}
                onRequestClose={() => setIsOpen(false)}
                ariaHideApp={false}
                style={{
                    overlay: {
                        position: "fixed",
                        display: "flex",
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                        backgroundColor: "rgba(3, 6, 60, 0.7)",
                        zIndex: 9999,
                        padding: "60px 15px",
                    },
                    content: {
                        position: "static",
                        width: '900px',
                        margin: "auto",
                        border: "0px solid #5414D0",
                        // background: "#5414D0",
                        overflow: "auto",
                        WebkitOverflowScrolling: "touch",
                        borderRadius: "4px",
                        outline: "none",
                        padding: "15px",
                    },
                }}
            >
                <>
                    {headers.length > 0 && (
                        <>
                            <div className="wprf-modal-preview-header">
                                {csvData?.title}
                            </div>
                            <div className='wprf-modal-table-wrapper'>
                                <table>
                                    <thead>
                                        <tr>
                                            {headers.map(header => (
                                                <th key={header}>{header}</th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {paginatedData.map((row, index) => (
                                            <tr key={index}>
                                                {headers.map(header => (
                                                    <td key={header}> {header != 'image' ? row[header] : ''} <img width={100} src={header == 'image' ? row[header] : ''} /> </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <div className="wprf-modal-preview-footer">
                                <div className="items-per-page-wrapper">
                                    <SelectControl
                                        options={[
                                            { value: "5", label: __("5") },
                                            { value: "10", label: __("10") },
                                            { value: "20", label: __("20") },
                                            { value: "50", label: __("50") },
                                            { value: "100", label: __("100") },
                                        ]}
                                        value={itemsPerPage.toString()}
                                        onChange={(value) => handleItemsPerPageChange(value)}
                                    />
                                    <label htmlFor="">{__('Items Per Page')}</label>
                                </div>
                                <div className='pagination-wrapper'>
                                    <div className="pagination-info">
                                        {`Displaying ${startIndex}-${endIndex} of ${totalItems}`}
                                    </div>
                                    {/* @ts-ignore  */}
                                    <Pagination
                                        current={currentPage}
                                        onChange={handlePageChange}
                                        total={csvLocalMemoizedValue.length}
                                        pageSize={itemsPerPage}
                                        showTitle={false}
                                    />
                                </div>
                            </div>
                        </>
                    )}
                </>
            </ReactModal>
        </div>
    )
}

export default withLabel(Media);