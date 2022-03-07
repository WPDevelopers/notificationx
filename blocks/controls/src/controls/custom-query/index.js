/**
 * WordPress Core Dependencies
*/
// const { __ } = wp.i18n;
// const {
//     PanelBody,
//     PanelRow,
//     SelectControl,
//     TextControl,
// } = wp.components;
// const { useEffect, useState } = wp.element;

import { __ } from "@wordpress/i18n";
import {
    PanelBody,
    PanelRow,
    SelectControl,
    TextControl,
} from "@wordpress/components";
import {
    useEffect,
    useState
} from "@wordpress/element";

/**
 * External Dependencies
*/
import Select2 from 'react-select';
import AsyncSelect from 'react-select/async';

/**
 * Internal Dependencies
*/
import { SOURCES, ORDER_BY, ORDER } from './constants';
import {
    getPosts,
    getAuthor,
    getCategory,
    getTag,
    getLatest5Posts,
    getPostsBySearchString
} from './apiData';

export default function Inspector(props) {
    const { queryData, queryResults, setAttributes, initialOpen } = props;
    const [authorOptions, setAuthorOptions] = useState([]); //Author Data for Select2 Format
    const [categoryOptions, setCategoryOptions] = useState([]); //Categories Data for Select2 Format
    const [tagOptions, setTagOptions] = useState([]); //Tags Data for Select2 Format
    const [postOptions, setPostOptions] = useState([]); //Include/Exclude post Data for Select2 Format
    const [query, setQuery] = useState({});
    const [didMount, setDidMount] = useState(false);

    useEffect(() => {
        setDidMount(true);

        //If Exixsting query data exists in Attributes, set existing data to current query data
        if (typeof (queryData) != 'undefined' && Object.keys(queryData).length > 0) {
            setQuery({ ...queryData })
        }
        else {
            setQuery({
                source: 'posts',
                author: '',
                categories: '',
                tags: '',
                per_page: '6',
                offset: '0',
                orderby: 'date',
                order: 'desc',
                include: '',
                exclude: ''
            })
        }

        //Get Author data and Set Author Options Data
        getAuthor().then((authorData) => {
            let authorDataForOptions = [];
            authorData.length > 0 && authorData.forEach((item) => {
                let filterAuthorData = {};
                Object.keys(item).forEach(key => {
                    if (key === 'name') {
                        filterAuthorData.label = item[key];
                    }
                    if (key === 'id') {
                        filterAuthorData.value = item[key];
                    }
                });
                authorDataForOptions.push(filterAuthorData);
            })
            setAuthorOptions(authorDataForOptions)
        });

        //Get Categories data and Set Categories Options Data
        getCategory().then((categoryData) => {
            let categoryDataForOptions = [];
            categoryData.length > 0 && categoryData.forEach((item) => {
                let filterCategoryData = {};
                Object.keys(item).forEach(key => {
                    if (key === 'name') {
                        filterCategoryData.label = item[key];
                    }
                    if (key === 'id') {
                        filterCategoryData.value = item[key];
                    }
                });
                categoryDataForOptions.push(filterCategoryData);
            })
            setCategoryOptions(categoryDataForOptions)
        });

        //Get Tags data and Set Tags Options Data
        getTag().then((tagData) => {
            let tagDataForOptions = [];
            tagData.length > 0 && tagData.forEach((item) => {
                let filterTagData = {};
                Object.keys(item).forEach(key => {
                    if (key === 'name') {
                        filterTagData.label = item[key];
                    }
                    if (key === 'id') {
                        filterTagData.value = item[key];
                    }
                });
                tagDataForOptions.push(filterTagData);
            })
            setTagOptions(tagDataForOptions)
        });

        //Get Posts data and Set Tags Options Data
        getLatest5Posts().then((postData) => {
            let postDataForOptions = [];
            postData.length > 0 && postData.forEach((item) => {
                let filterPostData = {};
                Object.keys(item).forEach(key => {
                    if (key === 'title') {
                        filterPostData.label = item[key].rendered;
                    }
                    if (key === 'id') {
                        filterPostData.value = item[key];
                    }
                });
                postDataForOptions.push(filterPostData);
            })
            setPostOptions(postDataForOptions)
        });

        return () => {
            setDidMount(false)
        }
    }, []);

    useEffect(() => {
        //Get Post data on query change and set to Attributes
        if (didMount) {
            getPosts(query).then((posts) => {
                setAttributes({ queryData: query })
                setAttributes({ queryResults: posts })
            });
        }

    }, [query]);

    //Function for update query data on Source Select
    const setSource = (value) => {
        let updatedQueryData = { ...query };
        updatedQueryData.source = value;
        setQuery(updatedQueryData);
    }

    //Function for update query data on Author Select
    const setAuthor = (author) => {
        let updatedQueryData = { ...query };
        updatedQueryData.author = JSON.stringify(author);
        setQuery(updatedQueryData);
    };

    //Function for update query data on Category Select
    const setCategories = (categories) => {
        let updatedQueryData = { ...query };
        updatedQueryData.categories = JSON.stringify(categories);
        setQuery(updatedQueryData);
    };

    //Function for update query data on Tag Select
    const setTags = (tags) => {
        let updatedQueryData = { ...query };
        updatedQueryData.tags = JSON.stringify(tags);
        setQuery(updatedQueryData);
    };

    //Function for update query data on Tag Select
    const setIncludedPost = (posts) => {
        let updatedQueryData = { ...query };
        updatedQueryData.include = JSON.stringify(posts);
        setQuery(updatedQueryData);
    };

    //Function for update query data on Tag Select
    const setExcludePost = (posts) => {
        let updatedQueryData = { ...query };
        updatedQueryData.exclude = JSON.stringify(posts);
        setQuery(updatedQueryData);
    };

    //Function for update query data on Post Per Page Select
    const setPerPage = (number) => {
        let updatedQueryData = { ...query };
        updatedQueryData.per_page = number;
        setQuery(updatedQueryData);
    };

    //Function for update query data on Offset Select
    const setOffset = (number) => {
        let updatedQueryData = { ...query };
        updatedQueryData.offset = number;
        setQuery(updatedQueryData);
    };

    //Function for update query data on Order By Select
    const setOrderBy = (value) => {
        let updatedQueryData = { ...query };
        updatedQueryData.orderby = value;
        setQuery(updatedQueryData);
    }

    //Function for update query data on Order Select
    const setOrder = (value) => {
        let updatedQueryData = { ...query };
        updatedQueryData.order = value;
        setQuery(updatedQueryData);
    }

    //Function for update query data on Order Select
    const LoadIncludePosts = (value) => {
        return getPostsBySearchString(value).then((postData) => {
            let postDataForOptions = [];
            postData.length > 0 && postData.forEach((item) => {
                let filterPostData = {};
                Object.keys(item).forEach(key => {
                    if (key === 'title') {
                        filterPostData.label = item[key];
                    }
                    if (key === 'id') {
                        filterPostData.value = item[key];
                    }
                });
                postDataForOptions.push(filterPostData);
            })
            return postDataForOptions;
        });
    }

    return (
        <PanelBody title={__("Query", "essential-blocks")} initialOpen={initialOpen}>
            {typeof (query) != 'undefined' && didMount && (
                <>
                    <SelectControl
                        label={__("Source", "essential-blocks")}
                        value={query.source}
                        options={SOURCES}
                        onChange={(selected) => setSource(selected)}
                    />

                    <div className="eb-control-item-wrapper">
                        <PanelRow>Author</PanelRow>
                        <Select2
                            name='select-author'
                            value={query.author.length > 0 ? JSON.parse(query.author) : ''}
                            onChange={(selected) => setAuthor(selected)}
                            options={authorOptions}
                            isMulti='true'
                        />
                    </div>

                    {query.source === 'posts' && (
                        <>
                            <div className="eb-control-item-wrapper">
                                <PanelRow>Categories</PanelRow>
                                <Select2
                                    name='select-categories'
                                    value={query.categories.length > 0 ? JSON.parse(query.categories) : ''}
                                    onChange={(selected) => setCategories(selected)}
                                    options={categoryOptions}
                                    isMulti='true'
                                />
                            </div>
                            <div className="eb-control-item-wrapper">
                                <PanelRow>Tags</PanelRow>
                                <Select2
                                    name='select-tags'
                                    value={query.tags.length > 0 ? JSON.parse(query.tags) : ''}
                                    onChange={(selected) => setTags(selected)}
                                    options={tagOptions}
                                    isMulti='true'
                                />
                            </div>
                        </>
                    )}

                    <div className="eb-control-item-wrapper">
                        <PanelRow>Posts Include</PanelRow>
                        <AsyncSelect
                            cacheOptions
                            value={query.include.length > 0 ? JSON.parse(query.include) : ''}
                            defaultOptions={postOptions}
                            loadOptions={LoadIncludePosts}
                            onChange={(selected) => setIncludedPost(selected)}
                            isMulti='true'
                        />
                    </div>

                    <div className="eb-control-item-wrapper">
                        <PanelRow>Posts Exclude</PanelRow>
                        <AsyncSelect
                            cacheOptions
                            value={query.exclude.length > 0 ? JSON.parse(query.exclude) : ''}
                            defaultOptions={postOptions}
                            loadOptions={LoadIncludePosts}
                            onChange={(selected) => setExcludePost(selected)}
                            isMulti='true'
                        />
                    </div>

                    <TextControl
                        label="Posts Per Page"
                        type={"number"}
                        value={query.per_page}
                        onChange={(selected) => setPerPage(selected)}
                    />

                    <TextControl
                        label="Offset"
                        type={"number"}
                        value={query.offset}
                        onChange={(selected) => setOffset(selected)}
                    />

                    <SelectControl
                        label={__("Order By", "essential-blocks")}
                        value={query.orderby}
                        options={ORDER_BY}
                        onChange={(selected) => setOrderBy(selected)}
                    />

                    <SelectControl
                        label={__("Order", "essential-blocks")}
                        value={query.order}
                        options={ORDER}
                        onChange={(selected) => setOrder(selected)}
                    />
                </>
            )}
        </PanelBody>
    );
}