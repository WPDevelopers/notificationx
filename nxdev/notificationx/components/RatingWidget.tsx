import { __ } from '@wordpress/i18n';
import React, { Fragment, useEffect, useState } from 'react';
import nxHelper from '../core/functions';

const RatingWidget = () => {

    // @ts-ignore 
    const isSharedFeedback = notificationxTabs?.nx_feedback_shared;
    const [rating, setRating] = useState(null);
    const [hover, setHover] = useState(-1);
    const [ratingSubmitted, setRatingSubmitted] = useState(false);
    const [reviewMessage, setReviewMessage] = useState('');

    useEffect(() => {
        if (rating === 5) {
            sendRating(rating, reviewMessage);
            setRatingSubmitted(true);
        }
    }, [rating]);

    const sendRating = async (rating, reviewMessage) => {
        try {
            await nxHelper.post('index.php?rest_route=/notificationx/v1/send-rating', {
                rating,
                review: reviewMessage,
            });
        } catch (err) {
            console.error(err);
        }
    };

    return (
        <div className="notificationx-rating-widget sidebar-widget nx-widget">
            { !isSharedFeedback &&
                <div className="nx-widget-title-wrapper">
                    <div className="nx-widget-title">
                        { !ratingSubmitted &&
                            <Fragment>
                                {!rating && <h4>{__('Rate NotificationX', 'notificationx')}</h4>}
                                {!rating && (
                                    <div className="nx-widget-rating-area">
                                        {[...Array(5)].map((_, index) => {
                                            const ratingValue = index + 1;
                                            return (
                                                <svg
                                                    key={index}
                                                    width="32"
                                                    height="32"
                                                    viewBox="0 0 32 32"
                                                    fill={(ratingValue <= (hover || rating) || hover == -1) ? '#ffc107' : '#e4e5e9'}
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    onMouseEnter={() => setHover(ratingValue)}
                                                    onMouseLeave={() => setHover(null)}
                                                    onClick={() => setRating(ratingValue)}
                                                    style={{ cursor: 'pointer', transition: 'fill 0.2s' }}
                                                >
                                                    <path xmlns="http://www.w3.org/2000/svg" d="M6.1822 4.8034L1.3972 5.49715L1.31245 5.5144C1.18415 5.54846 1.06719 5.61596 0.973516 5.71001C0.879839 5.80405 0.812799 5.92127 0.779243 6.0497C0.745687 6.17813 0.746817 6.31316 0.782517 6.44101C0.818217 6.56886 0.887209 6.68494 0.982446 6.7774L4.44895 10.1517L3.63145 14.9179L3.6217 15.0004C3.61384 15.1331 3.6414 15.2655 3.70153 15.384C3.76167 15.5026 3.85223 15.603 3.96395 15.675C4.07566 15.7471 4.20451 15.7881 4.33731 15.794C4.4701 15.7998 4.60207 15.7703 4.7197 15.7084L8.9992 13.4584L13.2689 15.7084L13.3439 15.7429C13.4677 15.7917 13.6023 15.8066 13.7338 15.7862C13.8652 15.7658 13.9889 15.7108 14.0921 15.6269C14.1953 15.5429 14.2744 15.433 14.3211 15.3084C14.3678 15.1838 14.3805 15.049 14.3579 14.9179L13.5397 10.1517L17.0077 6.77665L17.0662 6.7129C17.1498 6.60998 17.2046 6.48674 17.225 6.35575C17.2454 6.22476 17.2308 6.09069 17.1825 5.9672C17.1343 5.84372 17.0541 5.73523 16.9503 5.65279C16.8465 5.57035 16.7227 5.51691 16.5914 5.4979L11.8064 4.8034L9.66745 0.468404C9.60555 0.342805 9.50973 0.23704 9.39084 0.163082C9.27194 0.0891243 9.13472 0.0499268 8.9947 0.0499268C8.85468 0.0499268 8.71745 0.0891243 8.59855 0.163082C8.47966 0.23704 8.38384 0.342805 8.32195 0.468404L6.1822 4.8034Z"/>
                                                </svg>
                                            );
                                        })}
                                    </div>
                                )}
                                {(rating && rating < 5) && (
                                    <div className="nx-widget-review-box">
                                        <h4>{__('Help us make it better!', 'notificationx')}</h4>
                                        <div className="review-box">
                                            <label htmlFor="review-box-desc">{__('Please share what went wrong with The NotificationX so that we can improve further *', 'notificationx')}</label>
                                            <textarea
                                                id="review-box-desc"
                                                value={reviewMessage}
                                                onChange={(e) => setReviewMessage(e.target.value)}
                                            ></textarea>
                                        </div>
                                        <button disabled={ reviewMessage ? false : true } onClick={() => {
                                            sendRating(rating, reviewMessage)
                                            setRatingSubmitted(true);
                                            setRating(null);
                                            setReviewMessage('');
                                        }}>{__('Send', 'notificationx')}</button>
                                    </div>
                                )}
                            </Fragment>
                        }
                        
                        {ratingSubmitted && (
                            <div className="review-submitted-box">
                                <h5>{__('Done ', 'notificationx')}🎉</h5>
                                <p>{__('We really appreciate you taking the time to share your feedback with us.', 'notificationx')}</p>
                                { (rating === 5) &&
                                    <a href='https://wordpress.org/support/plugin/notificationx/reviews/#new-post' target='_blank'>
                                        { __('Rate the Plugin','notificationx') }
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="17" viewBox="0 0 16 17" fill="none">
                                        <path d="M6 3.63175L10 8.29842L6 12.9651" stroke="#6A4BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                }
                            </div>
                        )}
                    </div>
                </div>
            }
            <div className="nx-widget-initiate-chat">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="33" viewBox="0 0 32 33" fill="none">
                    <rect y="0.298462" width="32" height="32" rx="5.33333" fill="url(#paint0_linear_4156_3185)"/>
                    <g clip-path="url(#clip0_4156_3185)">
                        <path d="M24.9995 19.4801C24.9995 17.2465 23.7181 15.253 21.799 14.2833C21.7393 18.5661 18.2671 22.0383 13.9844 22.0979C14.9541 24.0171 16.9475 25.2985 19.1812 25.2985C20.2285 25.2985 21.2469 25.0196 22.1417 24.4896L24.9741 25.2731L24.1907 22.4407C24.7206 21.5458 24.9995 20.5274 24.9995 19.4801Z" fill="white"/>
                        <path d="M20.7461 14.1715C20.7461 10.3816 17.6629 7.29846 13.873 7.29846C10.0832 7.29846 7 10.3816 7 14.1715C7 15.4066 7.32877 16.6092 7.95306 17.665L7.02527 21.0191L10.3795 20.0915C11.4353 20.7158 12.6379 21.0446 13.873 21.0446C17.6629 21.0446 20.7461 17.9614 20.7461 14.1715ZM12.8184 12.5719H11.7637C11.7637 11.4087 12.7099 10.4625 13.873 10.4625C15.0362 10.4625 15.9824 11.4087 15.9824 12.5719C15.9824 13.1623 15.7325 13.7297 15.2965 14.1285L14.4004 14.9487V15.7711H13.3457V14.4842L14.5844 13.3504C14.8058 13.1479 14.9277 12.8714 14.9277 12.5719C14.9277 11.9903 14.4546 11.5172 13.873 11.5172C13.2915 11.5172 12.8184 11.9903 12.8184 12.5719ZM13.3457 16.8258H14.4004V17.8805H13.3457V16.8258Z" fill="white"/>
                    </g>
                    <defs>
                        <linearGradient id="paint0_linear_4156_3185" x1="16" y1="0.298462" x2="16" y2="32.2985" gradientUnits="userSpaceOnUse">
                            <stop offset="0.436632" stop-color="#6141FD"/>
                            <stop offset="1" stop-color="#9A86FC"/>
                        </linearGradient>
                        <clipPath id="clip0_4156_3185">
                            <rect width="18" height="18" fill="white" transform="translate(7 7.29846)"/>
                        </clipPath>
                    </defs>
                </svg>
                <h5>{__('We are here to help', 'notificationx')}</h5>
                <p>{__('Lorem ipsum dolor sit amet consectetur. Vitae tellus pretium', 'notificationx')}</p>
                <a href="https://wpdeveloper.com/contact?chatbox=show" target="_blank">{__('Initiate Chat', 'notificationx')}</a>
            </div>
        </div>
    );
};

export default RatingWidget;
