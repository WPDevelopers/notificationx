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

      <div className='nx-analytics-integration-wrapper'>
        <div className='nx-analytics-graph-wrapper nx-admin-content-wrapper'>
          <div className='nx-analytics-overlay'>
            <button className='nx-get-pro'>
              <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/pro-icon.svg" alt="icon" />
              Get Pro to Unlock
            </button>
          </div>
          <div className='nx-analytics-header nx-content-details header'>
            <h4>Analytics</h4>
            <button className='nx-secondary-btn'>View all</button>
          </div>
          <div className='nx-analytics-body'>
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/analytics-graph.png" alt="icon" />
          </div>
        </div>
        <div className='nx-integration-wrapper nx-admin-content-wrapper'>
          <div className='nx-integrations-header nx-content-details header'>
            <h4>Integrations</h4>
            <button className='nx-secondary-btn'>View all Integration</button>
          </div>
          <div className='nx-integrations-body'>
            <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/integration.png" alt="icon" />
          </div>
        </div>
      </div>

      <div className='nx-other-details-wrapper'>
        <div className='nx-notification-type-wrapper nx-admin-content-wrapper'>
          <div className='nx-notification-type-header nx-content-details header'>
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

        <div className='nx-resource-stories-wrapper'>
          <div className='nx-resource-wrapper nx-admin-content-wrapper'>
            <div className='nx-resource-header nx-content-details header'>
              <h4>Helpful Resources</h4>
              <button className='nx-secondary-btn'>Explore More</button>
            </div>

            <div className='nx-resource-body'>
              <div className='nx-resource-content nx-content-details'>
                <span>
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 0.75C7.36831 0.75 5.77325 1.23385 4.41655 2.14038C3.05984 3.0469 2.00242 4.33537 1.378 5.84286C0.753575 7.35035 0.590197 9.00915 0.908525 10.6095C1.22685 12.2098 2.01259 13.6798 3.16637 14.8336C4.32016 15.9874 5.79017 16.7732 7.39051 17.0915C8.99085 17.4098 10.6497 17.2464 12.1571 16.622C13.6646 15.9976 14.9531 14.9402 15.8596 13.5835C16.7661 12.2268 17.25 10.6317 17.25 9C17.2487 6.81237 16.3791 4.71471 14.8322 3.16782C13.2853 1.62093 11.1876 0.751311 9 0.75ZM8.25 3.75C8.25 3.55109 8.32902 3.36032 8.46967 3.21967C8.61033 3.07902 8.80109 3 9 3C9.19892 3 9.38968 3.07902 9.53033 3.21967C9.67099 3.36032 9.75 3.55109 9.75 3.75V6.75C9.75 6.94891 9.67099 7.13968 9.53033 7.28033C9.38968 7.42098 9.19892 7.5 9 7.5C8.80109 7.5 8.61033 7.42098 8.46967 7.28033C8.32902 7.13968 8.25 6.94891 8.25 6.75V3.75ZM9 14.25C7.87174 14.2505 6.77335 13.8874 5.86774 13.2145C4.96212 12.5415 4.29749 11.5946 3.97243 10.5142C3.64737 9.4338 3.67918 8.27738 4.06315 7.21646C4.44711 6.15554 5.16279 5.24662 6.10403 4.6245C6.18611 4.56739 6.27878 4.52727 6.3766 4.5065C6.47441 4.48574 6.57539 4.48474 6.6736 4.50357C6.77181 4.5224 6.86526 4.56067 6.94845 4.61615C7.03165 4.67162 7.10291 4.74317 7.15805 4.82659C7.21319 4.91001 7.25109 5.00362 7.26952 5.1019C7.28795 5.20018 7.28654 5.30116 7.26538 5.39888C7.24422 5.49661 7.20372 5.58913 7.14628 5.67098C7.08884 5.75283 7.01561 5.82237 6.9309 5.8755C6.25872 6.3202 5.7477 6.96964 5.47357 7.72757C5.19944 8.48549 5.1768 9.31156 5.409 10.0834C5.64119 10.8552 6.11587 11.5316 6.76269 12.0125C7.4095 12.4933 8.19403 12.753 9 12.753C9.80598 12.753 10.5905 12.4933 11.2373 12.0125C11.8841 11.5316 12.3588 10.8552 12.591 10.0834C12.8232 9.31156 12.8006 8.48549 12.5264 7.72757C12.2523 6.96964 11.7413 6.3202 11.0691 5.8755C10.9844 5.82237 10.9112 5.75283 10.8537 5.67098C10.7963 5.58913 10.7558 5.49661 10.7346 5.39888C10.7135 5.30116 10.7121 5.20018 10.7305 5.1019C10.7489 5.00362 10.7868 4.91001 10.842 4.82659C10.8971 4.74317 10.9684 4.67162 11.0516 4.61615C11.1348 4.56067 11.2282 4.5224 11.3264 4.50357C11.4246 4.48474 11.5256 4.48574 11.6234 4.5065C11.7212 4.52727 11.8139 4.56739 11.896 4.6245C12.8372 5.24662 13.5529 6.15554 13.9369 7.21646C14.3208 8.27738 14.3526 9.4338 14.0276 10.5142C13.7025 11.5946 13.0379 12.5415 12.1323 13.2145C11.2267 13.8874 10.1283 14.2505 9 14.25Z" fill="#ADA6D6" />
                  </svg>
                </span>
                <p>How To Get Started With The Quick Builder Of NotificationX?</p>
              </div>
              <div className='nx-resource-content nx-content-details'>
                <span>
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_808_2276)">
                      <path d="M17.9995 12.1817C17.9995 9.94801 16.7181 7.95454 14.799 6.98486C14.7393 11.2676 11.2671 14.7398 6.98438 14.7994C7.95406 16.7186 9.94753 18 12.1812 18C13.2285 18 14.2469 17.7211 15.1417 17.1912L17.9741 17.9746L17.1907 15.1422C17.7206 14.2474 17.9995 13.2289 17.9995 12.1817Z" fill="#6A4BFF" />
                      <path d="M13.7461 6.87305C13.7461 3.08318 10.6629 0 6.87305 0C3.08318 0 0 3.08318 0 6.87305C0 8.10819 0.328766 9.31078 0.953064 10.3666L0.0252686 13.7207L3.37953 12.793C4.43532 13.4173 5.63791 13.7461 6.87305 13.7461C10.6629 13.7461 13.7461 10.6629 13.7461 6.87305ZM5.81836 5.27344H4.76367C4.76367 4.11026 5.70987 3.16406 6.87305 3.16406C8.03623 3.16406 8.98242 4.11026 8.98242 5.27344C8.98242 5.86382 8.73248 6.43126 8.29646 6.83006L7.40039 7.65019V8.47266H6.3457V7.18575L7.58441 6.05196C7.80579 5.8494 7.92774 5.57295 7.92774 5.27344C7.92774 4.69185 7.45464 4.21875 6.87305 4.21875C6.29146 4.21875 5.81836 4.69185 5.81836 5.27344ZM6.3457 9.52734H7.40039V10.582H6.3457V9.52734Z" fill="#6A4BFF" />
                    </g>
                    <defs>
                      <clipPath id="clip0_808_2276">
                        <rect width="18" height="18" fill="white" />
                      </clipPath>
                    </defs>
                  </svg>
                </span>
                <p>How To Collect API Key From Google Console & Set Up Business Account?</p>
              </div>
              <div className='nx-resource-content nx-content-details'>
                <span>
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12.2514 3.35257C11.9195 3.15569 11.6917 2.81875 11.6495 2.43288L11.5286 1.34895C11.5033 1.12423 11.3458 0.936352 11.1292 0.871945C9.73701 0.459352 8.26329 0.459352 6.8711 0.871945C6.65454 0.936352 6.49703 1.12423 6.47172 1.34895C6.47172 1.34895 6.41545 1.85238 6.35076 2.35273V2.36257C6.30576 2.7625 6.07515 3.11801 5.72921 3.32219C5.37484 3.52244 4.94734 3.55647 4.57609 3.39363L3.63951 2.98441C3.43138 2.89357 3.19234 2.93576 3.0264 3.09157C1.97453 4.09001 1.23763 5.36716 0.897321 6.7782C0.846696 6.99841 0.931074 7.22847 1.11107 7.36263C1.11107 7.36263 1.54703 7.68297 1.9689 7.99319C2.27828 8.22044 2.46109 8.581 2.46109 8.96463C2.46109 9.3896 2.2586 9.78954 1.91548 10.0415L1.11107 10.633C0.931074 10.7672 0.846696 10.9972 0.897321 11.2174C1.23763 12.6285 1.97453 13.9056 3.0264 14.9043C3.19234 15.0599 3.43138 15.1021 3.63951 15.0112C3.63951 15.0112 4.17109 14.7789 4.67452 14.5671C4.67734 14.566 4.67734 14.5649 4.68015 14.564C5.02609 14.4136 5.41985 14.433 5.7461 14.6166C6.07798 14.8073 6.30295 15.1392 6.34795 15.5203L6.47172 16.6512C6.49703 16.8751 6.65172 17.0624 6.86829 17.1276C8.23516 17.5402 9.75673 17.5281 11.1264 17.129C11.3458 17.0655 11.5033 16.877 11.5286 16.6512C11.5286 16.6512 11.5961 16.0271 11.6692 15.459C11.6692 15.4567 11.6692 15.4542 11.6692 15.4519C11.7086 15.0945 11.9139 14.7764 12.2233 14.5936C12.5411 14.4102 12.9236 14.3826 13.2583 14.5294L14.3636 15.0138C14.5717 15.104 14.8108 15.0627 14.9739 14.9086C16.0145 13.9315 16.7654 12.6082 17.1058 11.2222C17.1592 11.0014 17.0749 10.77 16.892 10.6353L15.9723 9.95772C15.6545 9.72541 15.4689 9.35669 15.4689 8.96491C15.4689 8.61448 15.6348 8.28485 15.9189 8.07701L16.892 7.3601C17.072 7.22679 17.1592 6.99785 17.1058 6.77876C16.7795 5.3891 16.0089 4.07791 14.9795 3.09101C14.8136 2.93379 14.5717 2.89076 14.3636 2.98188C14.3636 2.98188 13.8573 3.20379 13.3652 3.41022C13.3623 3.41135 13.3595 3.41248 13.3567 3.41388C12.9995 3.57026 12.5917 3.54747 12.2514 3.35257ZM9.00015 6.18757C7.44765 6.18757 6.18765 7.44785 6.18765 9.00007C6.18765 10.5523 7.44765 11.8126 9.00015 11.8126C10.5527 11.8126 11.8126 10.5523 11.8126 9.00007C11.8126 7.44785 10.5527 6.18757 9.00015 6.18757Z" fill="#ADA6D6" />
                  </svg>
                </span>
                <p>How To Configure A Notification Bar In Gutenberg With NotificationX?</p>
              </div>
              <div className='nx-resource-content nx-content-details'>
                <span>
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12.2514 3.35257C11.9195 3.15569 11.6917 2.81875 11.6495 2.43288L11.5286 1.34895C11.5033 1.12423 11.3458 0.936352 11.1292 0.871945C9.73701 0.459352 8.26329 0.459352 6.8711 0.871945C6.65454 0.936352 6.49703 1.12423 6.47172 1.34895C6.47172 1.34895 6.41545 1.85238 6.35076 2.35273V2.36257C6.30576 2.7625 6.07515 3.11801 5.72921 3.32219C5.37484 3.52244 4.94734 3.55647 4.57609 3.39363L3.63951 2.98441C3.43138 2.89357 3.19234 2.93576 3.0264 3.09157C1.97453 4.09001 1.23763 5.36716 0.897321 6.7782C0.846696 6.99841 0.931074 7.22847 1.11107 7.36263C1.11107 7.36263 1.54703 7.68297 1.9689 7.99319C2.27828 8.22044 2.46109 8.581 2.46109 8.96463C2.46109 9.3896 2.2586 9.78954 1.91548 10.0415L1.11107 10.633C0.931074 10.7672 0.846696 10.9972 0.897321 11.2174C1.23763 12.6285 1.97453 13.9056 3.0264 14.9043C3.19234 15.0599 3.43138 15.1021 3.63951 15.0112C3.63951 15.0112 4.17109 14.7789 4.67452 14.5671C4.67734 14.566 4.67734 14.5649 4.68015 14.564C5.02609 14.4136 5.41985 14.433 5.7461 14.6166C6.07798 14.8073 6.30295 15.1392 6.34795 15.5203L6.47172 16.6512C6.49703 16.8751 6.65172 17.0624 6.86829 17.1276C8.23516 17.5402 9.75673 17.5281 11.1264 17.129C11.3458 17.0655 11.5033 16.877 11.5286 16.6512C11.5286 16.6512 11.5961 16.0271 11.6692 15.459C11.6692 15.4567 11.6692 15.4542 11.6692 15.4519C11.7086 15.0945 11.9139 14.7764 12.2233 14.5936C12.5411 14.4102 12.9236 14.3826 13.2583 14.5294L14.3636 15.0138C14.5717 15.104 14.8108 15.0627 14.9739 14.9086C16.0145 13.9315 16.7654 12.6082 17.1058 11.2222C17.1592 11.0014 17.0749 10.77 16.892 10.6353L15.9723 9.95772C15.6545 9.72541 15.4689 9.35669 15.4689 8.96491C15.4689 8.61448 15.6348 8.28485 15.9189 8.07701L16.892 7.3601C17.072 7.22679 17.1592 6.99785 17.1058 6.77876C16.7795 5.3891 16.0089 4.07791 14.9795 3.09101C14.8136 2.93379 14.5717 2.89076 14.3636 2.98188C14.3636 2.98188 13.8573 3.20379 13.3652 3.41022C13.3623 3.41135 13.3595 3.41248 13.3567 3.41388C12.9995 3.57026 12.5917 3.54747 12.2514 3.35257ZM9.00015 6.18757C7.44765 6.18757 6.18765 7.44785 6.18765 9.00007C6.18765 10.5523 7.44765 11.8126 9.00015 11.8126C10.5527 11.8126 11.8126 10.5523 11.8126 9.00007C11.8126 7.44785 10.5527 6.18757 9.00015 6.18757Z" fill="#ADA6D6" />
                  </svg>
                </span>
                <p>How To Configure A Notification Bar In Gutenberg With NotificationX?</p>
              </div>
            </div>
          </div>

          <div className='nx-stories-wrapper nx-admin-content-wrapper'>
            <div className='nx-stories-header nx-content-details header'>
              <h4>Customer Success Stories</h4>
              <button className='nx-secondary-btn'>Explore More</button>
            </div>

            <div className='nx-stories-body'>
              <div className='nx-stories-content'>
                <img className='stories-bg' src="/wp-content/plugins/notificationx/assets/admin/images/new-img/stories-2.png" alt="stories img" />
                <div className='nx-content-details'>
                  <h5>How Emilio Johann Got 1.4M+ Views with NotificationX Sales Alert</h5>
                  <p>eCommerce & Entrepreneurship Consultant</p>
                  <div className='nx-author-details'>
                    <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/author-1.png" alt="author img" />
                    <div>
                      <h6>Emilio Johann</h6>
                      <p>Co-Founder & CEO at Barn2</p>
                    </div>
                  </div>
                </div>
              </div>

              <div className='nx-stories-content'>
                <img className='stories-bg' src="/wp-content/plugins/notificationx/assets/admin/images/new-img/stories-2.png" alt="stories img" />
                <div className='nx-content-details'>
                  <h5>Converting Prospects to Customers: Barn2's Success Story with NotificationX</h5>
                  <p>WordPress Plugin Developer Company</p>
                  <div className='nx-author-details'>
                    <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/author-2.png" alt="author img" />
                    <div>
                      <h6>Katie Keith</h6>
                      <p>Florida, USA.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

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
    </div>
  )
}

export default NewAdmin