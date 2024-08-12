import React from 'react'
import { FOOTER_DOCS } from '../../core/constants'
import { assetsURL } from '../../core/functions'

const Docs = ({ props, context }) => {
  return (
    <div className='nx-more-docs-wrapper'>
      {
        FOOTER_DOCS.map( (item) => (
          <div className='nx-docs-content-wrapper nx-content-details'>
            <div className='nx-docs-content-items'>
              <div className='img-wrap'>
                <img src={ assetsURL(`/images/new-img/${item?.image}`) } alt="icon" />
              </div>
              <h3>{ item?.title }</h3>
              <p>{ item?.desc }</p>
              <a className='nx-resource-link' target='_blank' href={item?.button_url}>
                { item?.button_text }
                <img src={ assetsURL(`/images/new-img/link.svg`) } alt="icon" />
              </a>
            </div>
          </div>
        ) )
      }
    </div>
  )
}

export default Docs