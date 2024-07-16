import React from 'react'

const Integration = ({props, context}) => {
  return (
    <div className='nx-admin-content-wrapper nx-notifications-wrapper'>
        <div className='nx-integrations-details nx-content-details header'>
          <h4>Integrations</h4>
          <button className='nx-primary-btn'>View all Notification</button>
        </div>
        <div className='nx-notifications-details'>
          <div className='nx-table-wrapper'>
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
            </div>
          </div>
        </div>
        <div className='notifications-not-found nx-content-details'>
          <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/not-found.svg" alt="icon" />
          <h5>NO NOTIFICATIONS ARE FOUND.</h5>
          <p>Seems like you haven’t created any notification alerts. Hit on "Add New" button to get started</p>
          <button className='nx-primary-btn'>Add New<img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/add.svg" alt="icon"></img></button>
        </div>
    </div>
  )
}

export default Integration