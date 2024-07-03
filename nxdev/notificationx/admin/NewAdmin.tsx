import React from 'react'

const NewAdmin = () => {
  return (
    <div className='nx-admin-wrapper'>
      <div className="nx-admin-header">
        <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/main-logo.svg" alt="logo" />
        <a className="nx-add-new-btn" href="#">
          Add New
          <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/add.svg" alt="icon" />
        </a>
      </div>

      <div className="nx-admin-content-wrapper nx-started">
        <div className='nx-started-wrapper'>
          <div className='nx-video-widget'>
            <a href="#">
              <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/video-widget.png" alt="video-widget" />
            </a>
          </div>
          <div className='nx-started-content nx-content-details'>
            <h2>Get Started with NotificationX</h2>
            <p>Elevate your website's engagement by creating dynamic notifications, sales banners, and more. Follow this guide to get started quickly and make the most of NotificationX's features.</p>
            <button className='nx-primary-btn'>Launch Setup Wizard</button>
            <a className='nx-resource-link' href="#">
              Read Starter Guide
              <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/link.svg" alt="icon" />
            </a>
          </div>
        </div>
      </div>

      <div className='nx-analytics-wrapper'>
        <div className='nx-analytics-content-wrapper'>
          <img src="/wp-content/plugins/notificationx/assets/admin/images/analytics/views-icon.png" alt="icon" />
          <div className='analytics-counter'>
            <span className="nx-counter-label">Total Views</span>
            <h3 className="nx-counter-number">656</h3>
          </div>
        </div>
        <div className='nx-analytics-content-wrapper'>
          <img src="/wp-content/plugins/notificationx/assets/admin/images/analytics/clicks-icon.png" alt="icon" />
          <div className='analytics-counter'>
            <span className="nx-counter-label">Total Clicks</span>
            <h3 className="nx-counter-number">65,753</h3>
          </div>
        </div>
        <div className='nx-analytics-content-wrapper'>
          <div>
            <img src="/wp-content/plugins/notificationx/assets/admin/images/analytics/ctr-icon.png" alt="icon" />
          </div>
          <div className='analytics-counter'>
            <span className="nx-counter-label">Click-Through-Rate</span>
            <h3 className="nx-counter-number">1.6544</h3>
          </div>
        </div>
      </div>

      <div className='nx-admin-content-wrapper nx-notifications-wrapper'>
        <div className='nx-integrations-details nx-content-details'>
          <h4>Integrations</h4>
          <button className='nx-primary-btn'>View all Notifiation</button>
        </div>
        <div className='nx-notifications-details'>
          <div className='notifications-list-header'>
            <span className='th'>NotificationX Title</span>
            <span className='th'>Preview</span>
            <span className='th'>Status</span>
            <span className='th'>Type</span>
            <span className='th'>Stats</span>
            <span className='th'>Date</span>
            <span className='th'>Action</span>
          </div>
          <div className='notifications-list-body'>
            <div className='notifications-list-wrapper'>
              <div className='notifications-list-items'>
                <span className='td'>NotificationX - Sales Notification - June 9, 2024</span>
                <span className='td'><img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/theme-01.png" alt="theme img" /></span>
                <span className='td'>
                  <label className="toggle-wrap">
                    <input type="checkbox" />
                    <span className="slider"></span>
                  </label>
                </span>
                <span className='td'>Sales Notification</span>
                <span className='td'>10 views</span>
                <span className='td'>
                  <span className='td--ex'>Published</span>
                  June 9, 2024 6:07 am
                </span>
                <span className='td action'>
                  <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/action-btn.svg" alt="icon" />
                </span>
              </div>
              <div className='notifications-list-items'>
                <span className='td'>NotificationX - Sales Notification - June 9, 2024</span>
                <span className='td'><img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/theme-2.png" alt="theme img" /></span>
                <span className='td'>
                  <label className="toggle-wrap">
                    <input type="checkbox" checked />
                    <span className="slider"></span>
                  </label>
                </span>
                <span className='td'>Sales Notification</span>
                <span className='td'>10 views</span>
                <span className='td'>
                  <span className='td--ex'>Published</span>
                  June 9, 2024 6:07 am
                </span>
                <span className='td action'>
                  <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/action-btn.svg" alt="icon" />
                </span>
              </div>
              <div className='notifications-list-items'>
                <span className='td'>NotificationX - Sales Notification - June 9, 2024</span>
                <span className='td'><img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/theme-01.png" alt="theme img" /></span>
                <span className='td'>
                  <label className="toggle-wrap">
                    <input type="checkbox" />
                    <span className="slider"></span>
                  </label>
                </span>
                <span className='td'>Sales Notification</span>
                <span className='td'>10 views</span>
                <span className='td'>
                  <span className='td--ex'>Published</span>
                  June 9, 2024 6:07 am
                </span>
                <span className='td action'>
                  <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/action-btn.svg" alt="icon" />
                </span>
              </div>
            </div>
            <div className='notifications-not-found nx-content-details'>
              <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/not-found.svg" alt="icon" />
              <h5>NO NOTIFICATIONS ARE FOUND.</h5>
              <p>Seems like you haven’t created any notification alerts. Hit on "Add New" button to get started</p>
              <button className='nx-primary-btn'>Add New<img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/add.svg" alt="icon"></img></button>
            </div>
          </div>
        </div>
      </div>

      <div className='nx-analytics-integration-wrapper'>
        <div className='nx-admin-content-wrapper'>
          <div className="nx-analytics-graph-wrapper">
            <h2>graps</h2>
          </div>
        </div>
      </div>
    </div>
  )
}

export default NewAdmin