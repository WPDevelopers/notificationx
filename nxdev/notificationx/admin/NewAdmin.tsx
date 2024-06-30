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
    </div>
  )
}

export default NewAdmin