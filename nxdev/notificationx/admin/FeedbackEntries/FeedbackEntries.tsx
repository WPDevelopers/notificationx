import React, { useEffect, useState, useRef, Fragment, useCallback } from 'react';
import { __, sprintf, _n } from '@wordpress/i18n';
import withDocumentTitle from '../../core/withDocumentTitle';
import nxHelper, { assetsURL } from '../../core/functions';
import { Header } from '../../components';
import Pagination from "rc-pagination";
import localeInfo from 'rc-pagination/es/locale/en_US';
import { SelectControl } from "@wordpress/components";
import { useNotificationXContext } from '../../hooks';
import AnalyticsOverview from '../Dashboard/AnalyticsOverview';
import nxToast from '../../core/ToasterMsg';
import { Link } from 'react-router-dom';
import searchIcon from '../../icons/searchIcon.svg';
import Select from "react-select";

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

    useEffect(() => {
        isMounted.current = true;
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
        if (currentPage === 0 || perPage === 0) return;
        fetchEntries();
    }, [currentPage, perPage, searchKey, reload]);

    const fetchEntries = async () => {
        try {
            setLoading(true);
            const controller = typeof AbortController === 'undefined' ? undefined : new AbortController();

            const response = await nxHelper.get(
                `feedback-entries?page=${currentPage}&per_page=${perPage}&s=${searchKey}`,
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
                console.log('Fetch aborted');
                return;
            }
            console.error('Error fetching feedback entries:', error);
        } finally {
            if (isMounted.current) {
                setLoading(false);
            }
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
    
    const changeSearchInputValue = (event) => {
        const term = event.target.value;
        setSearchKey(term);
        setShowSearchInput(true);
    }

    return (
        <div className='nx-feedback-wrapper-class'>
            {/* Always visible */}
            <div className="nx-admin-wrapper">
                <div className='notificationx-items' id="notificationx-feedback-wrapper">
                    <div className="nx-admin-items">
                        {/* Search Bar and Bulk Actions */}
                        <div className="nx-admin-header-actions">
                            {entries.some(entry => entry.checked) && (
                                <div className="nx-bulk-actions" style={{ marginRight: '10px' }}>
                                    <button
                                        className="wprf-control wprf-button nx-bulk-delete-btn"
                                        onClick={bulkDelete}
                                        style={{
                                            backgroundColor: '#dc3545',
                                            color: 'white',
                                            border: 'none',
                                            padding: '15px 20px',
                                            borderRadius: '10px',
                                            cursor: 'pointer'
                                        }}
                                    >
                                        {__('Delete Selected', 'notificationx')} ({entries.filter(entry => entry.checked).length})
                                    </button>
                                </div>
                            )}
                             <div id="nx-search-wrapper" className="nx-search-wrapper">
                                <div className={`input-box ${showSearchInput ? 'open' : ''}`}>
                                    <input type="text" id="search_input" className="nx-search-input" placeholder={'Search...'} value={searchInput}  onChange={(e) => changeSearchInputValue(e)} />
                                    <span className="icon input-search-icon" onClick={ () => setShowSearchInput(!showSearchInput)  }>
                                        <img src={searchIcon} alt={'search-icon'} />
                                    </span>
                                </div>
                            </div>
                             <Select
                                name="bulk-action"
                                className="bulk-action-select"
                                classNamePrefix="bulk-action-select"
                                isSearchable={false}
                            />
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
                                                    <td>{__("Email Address", 'notificationx')}</td>
                                                    <td>{__("Message", 'notificationx')}</td>
                                                    <td>{__("Name", 'notificationx')}</td>
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
                                                            <td>{entry.email || '-'}</td>

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

                                                            <td>{entry.name || '-'}</td>

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
                                                        {viewEntry.name && (
                                                            <div className="nx-entry-field">
                                                                <strong>{__('Name:', 'notificationx')}</strong>
                                                                <span>{viewEntry.name}</span>
                                                            </div>
                                                        )}
                                                        {viewEntry.email && (
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
