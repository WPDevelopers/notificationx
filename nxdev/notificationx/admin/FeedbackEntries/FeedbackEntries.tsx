import React, { useEffect, useState, useRef, Fragment } from 'react';
import { __ } from '@wordpress/i18n';
import withDocumentTitle from '../../core/withDocumentTitle';
import nxHelper, { assetsURL } from '../../core/functions';
import { Header } from '../../components';
import Pagination from "rc-pagination";
import localeInfo from 'rc-pagination/es/locale/en_US';
import { SelectControl } from "@wordpress/components";
import { useNotificationXContext } from '../../hooks';
import AnalyticsOverview from '../Dashboard/AnalyticsOverview';
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
    const builderContext = useNotificationXContext();
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
    const logoURL = assetsURL('images/logos/large-logo-icon.png');

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

    return (
        <div className='nx-feedback-wrapper-class'>
            {/* Always visible */}
            <Header />
            <AnalyticsOverview props={props} context={builderContext} />

            <div className="nx-admin-wrapper">
                <div className='notificationx-items' id="notificationx-feedback-wrapper">
                    <div className="nx-admin-items">
                        {/* Search Bar */}
                        <div className="nx-admin-header-actions">
                            <div className="wprf-control-wrapper wprf-type-button wprf-label-none nx-talk-to-support wprf-name-talk_to_support">
                                <div className="wprf-control-field">
                                    <a
                                        href="https://notificationx.com/support/?support=chat"
                                        target="_blank"
                                        className="wprf-control wprf-button wprf-href-btn nx-talk-to-support"
                                    >
                                        {__('Talk to Support', 'notificationx')}
                                    </a>
                                </div>
                            </div>
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
                                                    <th>
                                                        <div className="nx-all-selector">
                                                        <input
                                                            type="checkbox"
                                                            checked={checkAll}
                                                            onChange={handleSelectAll}
                                                            name="nx_all"
                                                        />
                                                        </div>
                                                    </th>
                                                    <th>{__("No", 'notificationx')}</th>
                                                    <th>{__("Date", 'notificationx')}</th>
                                                    <th>{__("Email Address", 'notificationx')}</th>
                                                    <th>{__("Message", 'notificationx')}</th>
                                                    <th>{__("Name", 'notificationx')}</th>
                                                    <th>{__("Action", 'notificationx')}</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    {entries.map((entry, index) => (
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

export default withDocumentTitle(FeedbackEntries, __("Feedback Entries", 'notificationx'));
