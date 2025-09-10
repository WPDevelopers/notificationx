import React, { useEffect, useState, useRef } from 'react';
import { __ } from '@wordpress/i18n';
import withDocumentTitle from '../../core/withDocumentTitle';
import nxHelper from '../../core/functions';
import { Header } from '../../components';
import Pagination from "rc-pagination";
import localeInfo from 'rc-pagination/es/locale/en_US';
import { SelectControl } from "@wordpress/components";

interface FeedbackEntry {
    id: number;
    date: string;
    name: string;
    email: string;
    message: string;
    title: string;
    theme: string;
    ip: string;
}

const FeedbackEntries = (props: any) => {
    const [entries, setEntries] = useState<FeedbackEntry[]>([]);
    const [loading, setLoading] = useState(true);
    const [checkAll, setCheckAll] = useState(false);
    const [checkedItems, setCheckedItems] = useState<number[]>([]);
    const [viewEntry, setViewEntry] = useState<FeedbackEntry | null>(null);
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage, setPerPage] = useState(20);
    const [totalItems, setTotalItems] = useState(0);
    const [searchKey, setSearchKey] = useState('');
    const [searchInput, setSearchInput] = useState('');
    const isMounted = useRef(true);
    const searchTimeout = useRef<NodeJS.Timeout | null>(null);

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
    }, [currentPage, perPage, searchKey]);

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

    const handleSelectAll = (e: React.ChangeEvent<HTMLInputElement>) => {
        const checked = e.target.checked;
        setCheckAll(checked);
        if (checked) {
            setCheckedItems(entries.map(entry => entry.id));
        } else {
            setCheckedItems([]);
        }
    };

    const handleCheckItem = (id: number, checked: boolean) => {
        if (checked) {
            setCheckedItems(prev => [...prev, id]);
        } else {
            setCheckedItems(prev => prev.filter(item => item !== id));
            setCheckAll(false);
        }
    };

    const handleDelete = async (id: number) => {
        if (!confirm(__('Are you sure you want to delete this entry?', 'notificationx'))) {
            return;
        }

        try {
            await nxHelper.delete(`feedback-entries/${id}`);
            setEntries(prev => prev.filter(entry => entry.id !== id));
            setCheckedItems(prev => prev.filter(item => item !== id));
        } catch (error) {
            console.error('Error deleting entry:', error);
            alert(__('Failed to delete entry', 'notificationx'));
        }
    };

    const handleView = (entry: FeedbackEntry) => {
        setViewEntry(entry);
    };

    const closeModal = () => {
        setViewEntry(null);
    };

    if (loading) {
        return (
            <div>
                <Header />
                <div className="nx-admin-wrapper">
                    <div className="nx-loading">
                        {__('Loading feedback entries...', 'notificationx')}
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className='notificationx-items' id="notificationx-feedback-wrapper">
            <Header />
            <div className="nx-admin-items">
                {/* Search Bar */}
                <div className="nx-admin-header-actions">
                    <div className="nx-search-wrapper">
                        <input
                            type="text"
                            placeholder={__('Search entries...', 'notificationx')}
                            value={searchInput}
                            onChange={(e) => setSearchInput(e.target.value)}
                            className="nx-search-input"
                        />
                    </div>
                </div>
                <div className="nx-list-table-wrapper">
                    <table className="wp-list-table widefat fixed striped notificationx-list">
                        <thead>
                            <tr>
                                <td>
                                    <div className="nx-all-selector">
                                        <input 
                                            type="checkbox" 
                                            checked={checkAll} 
                                            onChange={handleSelectAll} 
                                            name="nx_all" 
                                        />
                                    </div>
                                </td>
                                <td>{__("No", 'notificationx')}</td>
                                <td>{__("Date", 'notificationx')}</td>
                                <td>{__("Email Address", 'notificationx')}</td>
                                <td>{__("Message", 'notificationx')}</td>
                                <td>{__("Name", 'notificationx')}</td>
                                <td>{__("Action", 'notificationx')}</td>
                            </tr>
                        </thead>
                        <tbody>
                            {entries.length === 0 ? (
                                <tr>
                                    <td colSpan={7} style={{ textAlign: 'center', padding: '40px' }}>
                                        {__('No feedback entries found', 'notificationx')}
                                    </td>
                                </tr>
                            ) : (
                                entries.map((entry, index) => (
                                    <tr key={entry.id}>
                                        <td>
                                            <input 
                                                type="checkbox" 
                                                checked={checkedItems.includes(entry.id)}
                                                onChange={(e) => handleCheckItem(entry.id, e.target.checked)}
                                            />
                                        </td>
                                        <td>{(currentPage - 1) * perPage + index + 1}</td>
                                        <td>{formatDate(entry.date)}</td>
                                        <td>{entry.email || '-'}</td>
                                        <td>
                                            <div style={{ maxWidth: '200px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
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
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination Controls */}
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
                                    <div className="nx-entry-field">
                                        <strong>{__('Popup Title:', 'notificationx')}</strong>
                                        <span>{viewEntry.title || '-'}</span>
                                    </div>
                                    <div className="nx-entry-field">
                                        <strong>{__('Theme:', 'notificationx')}</strong>
                                        <span>{viewEntry.theme || '-'}</span>
                                    </div>
                                    <div className="nx-entry-field">
                                        <strong>{__('IP Address:', 'notificationx')}</strong>
                                        <span>{viewEntry.ip || '-'}</span>
                                    </div>
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
            <style dangerouslySetInnerHTML={{
                __html: `
                    .nx-admin-header-actions {
                        margin-bottom: 20px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }
                    .nx-search-wrapper {
                        flex: 1;
                        max-width: 300px;
                    }
                    .nx-search-input {
                        width: 100%;
                        padding: 8px 12px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        font-size: 14px;
                    }
                    .nx-search-input:focus {
                        outline: none;
                        border-color: #0073aa;
                        box-shadow: 0 0 0 1px #0073aa;
                    }
                    .nx-admin-items-footer {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-top: 20px;
                        padding: 15px 0;
                        border-top: 1px solid #e1e1e1;
                    }
                    .nx-admin-items-footer .components-base-control {
                        margin-bottom: 0;
                        margin-right: 20px;
                    }
                    .nx-admin-items-footer .components-base-control__label {
                        font-weight: 600;
                        margin-bottom: 5px;
                    }
                    .rc-pagination {
                        display: flex;
                        align-items: center;
                        gap: 5px;
                    }
                    .rc-pagination-item,
                    .rc-pagination-prev,
                    .rc-pagination-next {
                        padding: 6px 12px;
                        border: 1px solid #d1d5db;
                        background: white;
                        color: #374151;
                        text-decoration: none;
                        border-radius: 4px;
                        cursor: pointer;
                        transition: all 0.2s ease;
                    }
                    .rc-pagination-item:hover,
                    .rc-pagination-prev:hover,
                    .rc-pagination-next:hover {
                        border-color: #0073aa;
                        color: #0073aa;
                    }
                    .rc-pagination-item-active {
                        background: #0073aa;
                        border-color: #0073aa;
                        color: white;
                    }
                    .rc-pagination-disabled {
                        opacity: 0.5;
                        cursor: not-allowed;
                    }
                    .rc-pagination-disabled:hover {
                        border-color: #d1d5db;
                        color: #374151;
                    }
                `
            }} />
        </div>
    );
};

export default withDocumentTitle(FeedbackEntries, __("Feedback Entries", 'notificationx'));
