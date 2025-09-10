import React, { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import withDocumentTitle from '../../core/withDocumentTitle';
import nxHelper from '../../core/functions';
import { Header } from '../../components';

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

    useEffect(() => {
        fetchEntries();
    }, []);

    const fetchEntries = async () => {
        try {
            setLoading(true);
            const response = await nxHelper.get('feedback-entries');
            // @ts-ignore 
            setEntries(response || []);
        } catch (error) {
            console.error('Error fetching feedback entries:', error);
        } finally {
            setLoading(false);
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
                                        <td>{index + 1}</td>
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
        </div>
    );
};

export default withDocumentTitle(FeedbackEntries, __("Feedback Entries", 'notificationx'));
