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
          <button className='nx-primary-btn'>View all Notification</button>
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
        <div className='nx-analytics-graph-wrapper nx-admin-content-wrapper'>
          <div className='nx-analytics-overlay'>
            <button className='nx-get-pro'>
              <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/pro-icon.svg" alt="icon" />
              Get Pro to Unlock
            </button>
          </div>
          <div className='nx-analytics-header nx-content-details'>
            <h4>Analytics</h4>
            <button className='nx-secondary-btn'>View all</button>
          </div>
          <div className='nx-analytics-body'>
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/analytics-graph.png" alt="icon" />
          </div>
        </div>
        <div className='nx-integration-wrapper nx-admin-content-wrapper'>
          <div className='nx-integrations-header nx-content-details'>
            <h4>Integrations</h4>
            <button className='nx-secondary-btn'>View all Integration</button>
          </div>
          <div className='nx-integrations-body'>
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/integration.png" alt="icon" />
          </div>
        </div>
      </div>

      <div className='nx-other-details-wrapper'>
        <div className='nx-notification-type nx-admin-content-wrapper'>
          <div className='nx-notification-type-header nx-content-details'>
            <div>
              <h4>Notification Type</h4>
              <p>We support various types of notifications including</p>
            </div>
            <button className='nx-secondary-btn'>
              Add New
              <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/add-icon-2.svg" alt="icon" />
            </button>
          </div>
          <div className='nx-notification-type-body'>
            <div className='nx-body-content-wrapper'>
              <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/theme-1.png" alt="icon" />
              <div className='nx-body-content nx-content-details'>
                <h5>Sales Notification</h5>
                <p>Showcase your latest sales to boost credibility and drive more conversions.</p>
                <button className='nx-secondary-btn'>Create Now</button>
              </div>
            </div>
            <div className='nx-body-content-wrapper'>
              <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/theme-3.png" alt="icon" />
              <div className='nx-body-content nx-content-details'>
                <h5>Review Notification</h5>
                <p>Showcase your latest sales to boost credibility and drive more conversions.</p>
                <button className='nx-secondary-btn'>Create Now</button>
              </div>
            </div>
            <div className='nx-body-content-wrapper'>
              <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/theme-4.png" alt="icon" />
              <div className='nx-body-content nx-content-details'>
                <h5>Notification Bar</h5>
                <p>Showcase your latest sales to boost credibility and drive more conversions.</p>
                <button className='nx-secondary-btn'>Create Now</button>
              </div>
            </div>
            <div className='nx-body-content-wrapper'>
              <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/theme-5.png" alt="icon" />
              <div className='nx-body-content nx-content-details'>
                <h5>Growth Alert</h5>
                <p>Showcase your latest sales to boost credibility and drive more conversions.</p>
                <button className='nx-secondary-btn'>Create Now</button>
              </div>
            </div>
            <div className='nx-body-content-wrapper'>
              <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/theme-6.png" alt="icon" />
              <div className='nx-body-content nx-content-details'>
                <h5>Flashing Tab</h5>
                <p>Showcase your latest sales to boost credibility and drive more conversions.</p>
                <button className='nx-secondary-btn'>Create Now</button>
              </div>
            </div>
            <div className='nx-body-content-wrapper'>
              <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/theme-7.png" alt="icon" />
              <div className='nx-body-content nx-content-details'>
                <h5>Cross - Domain</h5>
                <p>Showcase your latest sales to boost credibility and drive more conversions.</p>
                <button className='nx-secondary-btn'>Create Now</button>
              </div>
            </div>
          </div>
        </div>
        <div className='nx-notification-type nx-admin-content-wrapper'>
          <div className='nx-integrations-header nx-content-details'>
            <h4>Integrations</h4>
            <button className='nx-secondary-btn'>View all Integration</button>
          </div>
          <div className='nx-integrations-body'>
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/integration.png" alt="icon" />
          </div>
        </div>
      </div>
    </div>
  )
}

export default NewAdmin