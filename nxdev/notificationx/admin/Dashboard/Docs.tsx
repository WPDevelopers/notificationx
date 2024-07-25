import React from 'react'

const Docs = ( { props, context } ) => {
  return (
    <div className='nx-more-docs-wrapper'>
        <div className='nx-docs-content-wrapper nx-content-details'>
          <div className='img-wrap'>
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/love.svg" alt="icon" />
          </div>
          <h3>Show Your Love</h3>
          <p>We love to have you in NotificationX family. We are making it more awesome everyday. Take your 2 minutes to review the plugin and spread the love to encourage us to keep it going.</p>
          <a className='nx-resource-link' href="#">
            Leave a Review
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/link.svg" alt="icon" />
          </a>
        </div>
        <div className='nx-docs-content-wrapper nx-content-details'>
          <div className='img-wrap'>
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/docs.svg" alt="icon" />
          </div>
          <h3>Documentations</h3>
          <p>Get started by spending some time with the documentation to get familiar with NotificationX. Build awesome websites for you or your clients with ease.</p>
          <a className='nx-resource-link' href="#">
            Documentation
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/link.svg" alt="icon" />
          </a>
        </div>
        <div className='nx-docs-content-wrapper nx-content-details'>
          <div className='img-wrap'>
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/help.svg" alt="icon" />
          </div>
          <h3>Need Help?</h3>
          <p>Stuck with something? Get help from live chat or support ticket.</p>
          <a className='nx-resource-link' href="#">
            Get Support
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/link.svg" alt="icon" />
          </a>
        </div>
        <div className='nx-docs-content-wrapper nx-content-details'>
          <div className='img-wrap'>
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/contribute.svg" alt="icon" />
          </div>
          <h3>Contribute to NotificationX</h3>
          <p>You can contribute to make NotificationX better reporting bugs, creating issues, pull requests at GitHub.</p>
          <a className='nx-resource-link' href="#">
            Report A Bug
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/link.svg" alt="icon" />
          </a>
        </div>
    </div>
  )
}

export default Docs