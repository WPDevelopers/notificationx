import React, { useEffect, useState, useRef, Fragment, useCallback } from 'react';
import { __, sprintf, _n } from '@wordpress/i18n';
import withDocumentTitle from '../../core/withDocumentTitle';
import nxHelper, { assetsURL, getAlert, proAlert } from '../../core/functions';
import Pagination from "rc-pagination";
import localeInfo from 'rc-pagination/es/locale/en_US';
import { SelectControl } from "@wordpress/components";
import { useNotificationXContext } from '../../hooks';
import nxToast from '../../core/ToasterMsg';
import { Link } from 'react-router-dom';
import searchIcon from '../../icons/searchIcon.svg';
import Select from "react-select";
import ProIcon from '../../icons/ProIcon';

interface FeedbackEntry {
    id: number;
    date: string;
    name: string;
    email: string;
    message: string;
    title: string;
    theme: string;
    ip: string;
    notification_name: string;
    notification_id: number;
    nx_id: number;
    checked?: boolean;
}

const FeedbackEntries = (props: any) => {
    const builderContext = useNotificationXContext();
    const is_pro    = builderContext?.is_pro_active;
    const urlParams = new URLSearchParams(window.location.search);

    const [entries, setEntries] = useState<FeedbackEntry[]>([]);
    const [loading, setLoading] = useState(true);
    const [checkAll, setCheckAll] = useState(false);
    const [viewEntry, setViewEntry] = useState<FeedbackEntry | null>(null);
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage, setPerPage] = useState(20);
    const [totalItems, setTotalItems] = useState(0);
    const [searchKey, setSearchKey] = useState('');
    const [searchInput, setSearchInput] = useState('');
    const [reload, setReload] = useState(false);
    const isMounted = useRef(true);
    const searchTimeout = useRef<NodeJS.Timeout | null>(null);
    const logoURL = assetsURL('images/logos/large-logo-icon.png');
    const [showSearchInput, setShowSearchInput] = useState(false);
    const [popupNotifications, setPopupNotifications] = useState([]);
    const [selectedNotification, setSelectedNotification] = useState(null);
    const [notificationId, setNotificationId] = useState(urlParams.get('notification_id'));

    // Update the label when popup notifications are loaded
    useEffect(() => {
        if (popupNotifications.length > 0) {
            const notification = popupNotifications.find((notif: any) => notif.nx_id == notificationId);
            if (notification) {
                setSelectedNotification(prev => ({
                    ...prev,
                    label: notification.title || `Notification #${notification.nx_id}`,
                    value: notification.nx_id
                }));
            }
        }
    }, [popupNotifications]); // Only depend on popupNotifications, not selectedNotification

    useEffect(() => {
        isMounted.current = true;
        fetchPopupNotifications();
        return () => {
            isMounted.current = false;
        };
    }, []);

    // Debounced search
    useEffect(() => {
        if (searchTimeout.current) {
            clearTimeout(searchTimeout.current);
        }

        searchTimeout.current = setTimeout(() => {
            setSearchKey(searchInput);
            setCurrentPage(1);
        }, 500);

        return () => {
            if (searchTimeout.current) {
                clearTimeout(searchTimeout.current);
            }
        };
    }, [searchInput]);

    useEffect(() => {     
        if ( notificationId && selectedNotification?.value == notificationId ) {
            fetchEntries();
        }
    }, [selectedNotification]);

    useEffect(() => {
        if (currentPage === 0 || perPage === 0) return;
        if ( !notificationId ) {
            fetchEntries();
        }
    }, [ currentPage, perPage, searchKey, reload, selectedNotification ]);


    const fetchEntries = async () => {
        try {
            setLoading(true);
            const controller = typeof AbortController === 'undefined' ? undefined : new AbortController();

            // Build query parameters
            const queryParams = new URLSearchParams({
                page: currentPage.toString(),
                per_page: perPage.toString(),
                s: searchKey
            });

            // Add notification filter if selected
            if (selectedNotification?.value) {
                queryParams.append('notification_id', selectedNotification.value.toString());
            }

            const response = await nxHelper.get(
                `feedback-entries?${queryParams.toString()}`,
                { signal: controller?.signal }
            );

            if (controller?.signal?.aborted) {
                return;
            }

            if (isMounted.current) {
                // @ts-ignore
                setEntries(response?.entries || []);
                // @ts-ignore
                setTotalItems(response?.total || 0);
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            console.error('Error fetching feedback entries:', error);
        } finally {
            if (isMounted.current) {
                setLoading(false);
            }
        }
    };

    const fetchPopupNotifications = async () => {
        try {
            const controller = typeof AbortController === 'undefined' ? undefined : new AbortController();

            const response = await nxHelper.get(
                `nx?source=popup_notification&per_page=100`,
                { signal: controller?.signal }
            );

            if (controller?.signal?.aborted) {
                return;
            }

            // @ts-ignore 
            if (isMounted.current && response?.posts) {
                // @ts-ignore
                const popupNotification = response.posts.filter(item => item.source === 'popup_notification');
                setPopupNotifications(popupNotification);
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            console.error('Error fetching popup notifications:', error);
        }
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const selectAll = () => {
        const updatedEntries = entries.map((item, i) => {
            return {...item, checked: !checkAll};
        });
        setEntries(updatedEntries);
        setCheckAll(!checkAll);
    }

    const checkItem = (index: number) => {
        const updatedEntries = entries.map((item, i) => {
            if(index == i){
                return {...item, checked: !item?.checked};
            }
            return {...item};
        });
        setEntries(updatedEntries);
    }

    const handleDelete = async (id: number) => {
        nxHelper.swal({
            title: __("Are you sure?", 'notificationx'),
            text: __("You won't be able to revert this!", 'notificationx'),
            icon: "error",
            showCancelButton: true,
            confirmButtonText: __("Yes, Delete It", 'notificationx'),
            cancelButtonText: __("No, Cancel", 'notificationx'),
            reverseButtons: true,
            customClass: { actions: "nx-delete-actions" },
            confirmedCallback: () => {
                return nxHelper.delete(`feedback-entries/${id}`);
            },
            completeAction: (response) => {
                setEntries(prev => prev.filter(entry => entry.id !== id));
            },
            completeArgs: () => {
                return [
                    "deleted",
                    __("Feedback entry has been deleted.", "notificationx"),
                ];
            },
            afterComplete: () => { },
        });
    };

    const bulkDelete = async () => {
        // Get selected entries
        const selectedEntries = entries.filter(entry => entry.checked);

        if (selectedEntries.length === 0) {
            nxToast.error(__('Please select entries to delete', 'notificationx'));
            return;
        }

        nxHelper.swal({
            title: __("Are you sure?", 'notificationx'),
            html: sprintf(_n("You're about to delete %s feedback entry,<br />", "You're about to delete %s feedback entries,<br />", selectedEntries.length, 'notificationx'), selectedEntries.length) + __("You won't be able to revert this!", 'notificationx'),
            icon: "error",
            showCancelButton: true,
            confirmButtonText: __("Yes, Delete It", 'notificationx'),
            cancelButtonText: __("No, Cancel", 'notificationx'),
            reverseButtons: true,
            customClass: { actions: "nx-delete-actions" },
            confirmedCallback: () => {
                // Use bulk delete API for better performance
                const entryIds = selectedEntries.map(entry => entry.id);
                return nxHelper.post('feedback-entries/bulk-delete', {
                    ids: entryIds
                });
            },
            completeAction: (result) => {
                // Trigger reload to fetch fresh data
                setCheckAll(false);
                setReload(r => !r);
                return {all: selectedEntries.length};
            },
            completeArgs: (result) => {
                // translators: %d: Number of feedback entries deleted.
                return ["deleted", sprintf(_n(`%d feedback entry has been deleted.`, `%d feedback entries have been deleted.`, result?.all || 0, 'notificationx'), (result?.all || 0))];
            },
            afterComplete: () => { },
        });
    };

    const handleView = (entry: FeedbackEntry) => {
        setViewEntry(entry);
    };

    const closeModal = () => {
        setViewEntry(null);
    };

    const handleSearchIconClick = () => {
        if (showSearchInput && searchInput) {
            // Clear search if input is open and has content
            setSearchInput('');
            setSearchKey('');
        }
        setShowSearchInput(!showSearchInput);
    };

    const handleSearchKeyDown = (e) => {
        if (e.key === 'Enter') {
            setSearchKey(searchInput);
            setCurrentPage(1);
        } else if (e.key === 'Escape') {
            setSearchInput('');
            setSearchKey('');
            setShowSearchInput(false);
        }
    };

    const handleExport = async () => {
        try {
            // Show loading state
            nxToast.info(__('Preparing export...', 'notificationx'));

            // Prepare export data
            const exportData = {
                s: searchKey,
                notification_id: selectedNotification?.value || ''
            };

            // Make API call
            const response: any = await nxHelper.post('feedback-entries/export', exportData);
            if (response?.success && response?.csv_content) {
                // Create blob from CSV content
                const blob = new Blob([response.csv_content], { type: 'text/csv;charset=utf-8;' });

                // Create download link
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.href = url;
                link.download = response.filename || `notificationx-feedback-entries-${new Date().toISOString().split('T')[0]}.csv`;

                // Trigger download
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Clean up
                URL.revokeObjectURL(url);

                nxToast.connected(__(`Successfully exported ${response.total_entries || 0} entries`, 'notificationx'));
            } else {
                throw new Error(response?.message || 'Export failed');
            }

        } catch (error) {
            console.error('Export error:', error);
            nxToast.error(__('Failed to export entries', 'notificationx'));
        }
    };

    return (
        <div className='nx-feedback-wrapper-class'>
            {/* Always visible */}
            <div className="nx-admin-wrapper">
                <div className='notificationx-items' id="notificationx-feedback-wrapper">
                    <div className="nx-admin-items">
                        {/* Search Bar and Bulk Actions */}
                        <div className="nx-admin-header-actions">
                             <div id="nx-search-wrapper" className="nx-search-wrapper">
                                <div className={`input-box ${showSearchInput ? 'open' : ''}`}>
                                    <input
                                        type="text"
                                        id="search_input"
                                        className="nx-search-input"
                                        placeholder={__('Search...', 'notificationx')}
                                        value={searchInput}
                                        onChange={(e) => setSearchInput(e.target.value)}
                                        onKeyDown={handleSearchKeyDown}
                                    />
                                    {searchInput && showSearchInput && (
                                        <span
                                            className="icon input-clear-icon"
                                            onClick={() => {
                                                setSearchInput('');
                                                setSearchKey('');
                                            }}
                                            style={{
                                                position: 'absolute',
                                                right: '35px',
                                                top: '50%',
                                                transform: 'translateY(-50%)',
                                                cursor: 'pointer',
                                                fontSize: '16px',
                                                color: '#666'
                                            }}
                                        >
                                            ×
                                        </span>
                                    )}
                                    <span className="icon input-search-icon" onClick={handleSearchIconClick}>
                                        <img src={searchIcon} alt={'search-icon'} />
                                    </span>
                                </div>
                            </div>
                            {entries.some(entry => entry.checked) && (
                                <div className="nx-bulk-actions" style={{ marginRight: '10px' }}>
                                    <button
                                        className="wprf-control wprf-button nx-bulk-delete-btn"
                                        onClick={bulkDelete}
                                        style={{
                                            backgroundColor: '#dc3545',
                                            color: 'white',
                                            border: 'none',
                                            padding: '10px 20px',
                                            borderRadius: '5px',
                                            cursor: 'pointer'
                                        }}
                                    >
                                        {__('Delete Selected', 'notificationx')} ({entries.filter(entry => entry.checked).length})
                                    </button>
                                </div>
                            )}
                             <Select
                                name="bulk-action"
                                className="bulk-action-select"
                                classNamePrefix="bulk-action-select"
                                isSearchable={false}
                                placeholder={__('Filter by Notification', 'notificationx')}
                                value={selectedNotification}
                                onChange={(option) => {
                                    setNotificationId('');
                                    setSelectedNotification(option);
                                    setCurrentPage(1); // Reset to first page when filter changes
                                }}
                                options={[
                                    { value: '', label: __('All Notifications', 'notificationx') },
                                    ...popupNotifications.map((notification: any) => ({
                                        value: notification.nx_id,
                                        label: notification.title || `Notification #${notification.nx_id}`
                                    }))
                                ]}
                                isClearable
                            />
                            <button onClick={ handleExport } className='wprf-button'>
                                {__('Export', 'notificationx')}
                            </button>
                        </div>
                        {loading ? (
                            <div>
                                <div className="nx-list-table-wrapper">
                                    {__('Loading feedback entries...', 'notificationx')}
                                </div>
                            </div>
                        ) : ( 
                                <div>
                                    {/* Table */}
                                   <div className="nx-list-table-wrapper">
                                        {entries.length === 0 ? (
                                            <div className="nx-no-items">
                                                <img src={logoURL} />
                                                <h4>{__("No entries found.", 'notificationx')}</h4>
                                                <p>
                                                    {__(`Seems like you haven’t received any feedback entries yet.`, 'notificationx')}
                                                </p>
                                            </div>
                                        ) : (
                                            <table className="wp-list-table widefat fixed striped notificationx-list">
                                                <thead>
                                                    <tr>
                                                    <td>
                                                        <div className="nx-all-selector">
                                                        <input
                                                            type="checkbox"
                                                            checked={checkAll}
                                                            onChange={selectAll}
                                                            name="nx_all"
                                                        />
                                                        </div>
                                                    </td>
                                                    <td>{__("NotificationX Title", 'notificationx')}</td>
                                                    <td>{__("Date", 'notificationx')}</td>
                                                    <td>
                                                        {__("Email Address", 'notificationx')} 
                                                        <span style={ { marginLeft: '8px' } }>{!is_pro && <ProIcon />}</span>
                                                    </td>
                                                    <td>{__("Message", 'notificationx')}</td>
                                                    <td>
                                                        {__("Name", 'notificationx')}
                                                        <span style={ { marginLeft: '8px' } }>{!is_pro && <ProIcon />}</span>
                                                    </td>
                                                    <td>{__("Action", 'notificationx')}</td>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    {entries.map((entry, index) => (
                                                        <tr key={entry.id}>
                                                            <td>
                                                                <div className="nx-item-selector">
                                                                    <input
                                                                        type="checkbox"
                                                                        checked={entry.checked || false}
                                                                        onChange={() => checkItem(index)}
                                                                    />
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <Link to={{
                                                                    pathname: '/admin.php',
                                                                    search: `?page=nx-edit&id=${entry.notification_id}`,
                                                                }}>{entry.notification_name || entry.notification_id}</Link>
                                                            </td>
                                                            <td>{formatDate(entry.date)}</td>
                                                            <td
                                                                onClick={ () => {
                                                                    if( !is_pro ) {
                                                                         const popup = getAlert('popup', builderContext);
                                                                        proAlert(popup).fire();
                                                                    }
                                                                } }
                                                                style={{
                                                                    filter: !is_pro && entry.email ? "blur(3px)" : "none",
                                                                    userSelect: is_pro ? "auto" : "none",
                                                                }}
                                                            >
                                                            {
                                                            is_pro
                                                                ? (entry.email || '-')
                                                                : (entry.email ? 'example@mail.com' : '-')
                                                            }
                                                            </td>
                                                            <td>
                                                            <div style={{
                                                                maxWidth: '200px',
                                                                overflow: 'hidden',
                                                                textOverflow: 'ellipsis',
                                                                whiteSpace: 'nowrap'
                                                            }}>
                                                                {entry.message || '-'}
                                                            </div>
                                                            </td>
                                                            <td
                                                                onClick={ () => {
                                                                    if( !is_pro ) {
                                                                         const popup = getAlert('popup', builderContext);
                                                                        proAlert(popup).fire();
                                                                    }
                                                                } }
                                                                style={{
                                                                    filter: !is_pro && entry.name ? "blur(3px)" : "none",
                                                                    userSelect: is_pro ? "auto" : "none",
                                                                }}
                                                            >
                                                            {
                                                            is_pro
                                                                ? (entry.name || '-')
                                                                : (entry.name ? 'John Doe' : '-')
                                                            }
                                                            </td>

                                                            <td>
                                                                <div className="nx-action-buttons">
                                                                    <button
                                                                    className="nx-btn nx-btn-sm nx-btn-primary"
                                                                    onClick={() => handleView(entry)}
                                                                    title={__('View Details', 'notificationx')}
                                                                    >
                                                                    👁️
                                                                    </button>
                                                                    <button
                                                                    className="nx-btn nx-btn-sm nx-btn-danger"
                                                                    onClick={() => handleDelete(entry.id)}
                                                                    title={__('Delete Entry', 'notificationx')}
                                                                    >
                                                                    🗑️
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        )}
                                        </div>


                                    {/* Pagination */}
                                    {entries.length > 0 && (
                                        <div className="nx-admin-items-footer">
                                            <SelectControl
                                                label={__("Show Entries :", 'notificationx')}
                                                value={perPage.toString()}
                                                onChange={(p: string) => {
                                                    setPerPage(parseInt(p));
                                                    setCurrentPage(1);
                                                }}
                                                options={[
                                                    { value: "10", label: __("10", 'notificationx') },
                                                    { value: "20", label: __("20", 'notificationx') },
                                                    { value: "50", label: __("50", 'notificationx') },
                                                    { value: "100", label: __("100", 'notificationx') },
                                                    { value: "200", label: __("200", 'notificationx') },
                                                ]}
                                            />
                                            <Pagination
                                                current={currentPage}
                                                onChange={setCurrentPage}
                                                total={totalItems}
                                                pageSize={perPage}
                                                showTitle={false}
                                                hideOnSinglePage
                                                locale={localeInfo}
                                            />
                                        </div>
                                    )}

                                    {/* View Modal */}
                                    {viewEntry && (
                                        <div className="nx-modal-overlay" onClick={closeModal}>
                                            <div className="nx-modal" onClick={(e) => e.stopPropagation()}>
                                                <div className="nx-modal-header">
                                                    <h3>{__('Feedback Entry Details', 'notificationx')}</h3>
                                                    <button className="nx-modal-close" onClick={closeModal}>×</button>
                                                </div>
                                                <div className="nx-modal-body">
                                                    <div className="nx-entry-details">
                                                        <div className="nx-entry-field">
                                                            <strong>{__('Date:', 'notificationx')}</strong>
                                                            <span>{formatDate(viewEntry.date)}</span>
                                                        </div>
                                                        {(viewEntry.name && is_pro) && (
                                                            <div className="nx-entry-field">
                                                                <strong>{__('Name:', 'notificationx')}</strong>
                                                                <span>{viewEntry.name}</span>
                                                            </div>
                                                        )}
                                                        {(viewEntry.email && is_pro) && (
                                                            <div className="nx-entry-field">
                                                                <strong>{__('Email:', 'notificationx')}</strong>
                                                                <span>{viewEntry.email}</span>
                                                            </div>
                                                        )}
                                                        {viewEntry.message && (
                                                            <div className="nx-entry-field">
                                                                <strong>{__('Message:', 'notificationx')}</strong>
                                                                <div className="nx-message-content">{viewEntry.message}</div>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="nx-modal-footer">
                                                    <button className="nx-btn nx-btn-secondary" onClick={closeModal}>
                                                        {__('Close', 'notificationx')}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                    </div>
                </div>
            </div>
        </div>
    );

};

export default withDocumentTitle(FeedbackEntries, __("Feedback", 'notificationx'));
