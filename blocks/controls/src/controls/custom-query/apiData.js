// const apiFetch = wp.apiFetch;
import apiFetch from "@wordpress/api-fetch";

//Get Posts
export const getPosts = (query) => {
    let queryStringObj = {};
    if (query.source === 'posts') {
        let includesObjStr = ['author', 'categories', 'tags', 'include', 'exclude'];
        let includesDirectVal = ['per_page', 'offset', 'orderby', 'order'];
        Object.keys(query).forEach((key) => {
            if (includesObjStr.includes(key) && query[key].length > 0) {
                let data = JSON.parse(query[key]);
                let arg = [];
                if (data.length > 0) {
                    data.forEach((eachData) => {
                        if (typeof(eachData) === 'object') {
                            arg.push(eachData.value)
                        }
                    })
                    queryStringObj[key] = arg.join(',');
                }
                
            }
            else if (includesDirectVal.includes(key) && query[key].length > 0) {
                queryStringObj[key] = query[key];
            }
        })
    }
    else if (query.source === 'pages') {
        let includesObjStr = ['author'];
        let includesDirectVal = ['per_page', 'offset', 'orderby', 'order'];
        Object.keys(query).forEach((key) => {
            if (includesObjStr.includes(key) && query[key].length > 0) {
                let data = JSON.parse(query[key]);
                let arg = [];
                if (data.length > 0) {
                    data.forEach((eachData) => {
                        if (typeof(eachData) === 'object') {
                            arg.push(eachData.value)
                        }
                    })
                    queryStringObj[key] = arg.join(',');
                }
            }
            else if (includesDirectVal.includes(key) && query[key].length > 0) {
                queryStringObj[key] = query[key];
            }
        })
    }

    let queryString = Object.keys(queryStringObj)
    .map((key) => key + '=' + queryStringObj[key])
    .join('&');

    let postData = [];
    return apiFetch( { 
        path: `/wp/v2/${query.source}?${queryString}&_embed`,
    } )
    .then(
        ( result ) => {
            result != null && result.length > 0 && result.forEach((item) => {
                let filterPostData = {};
                Object.keys(item).forEach(key => {
                    filterPostData[key] = item[key];
                });
                postData = [...postData, filterPostData]
            })
            return postData;
        },
        ( error ) => {
            console.log("error",error)
        }
    );
}

//Get Author Data
export const getAuthor = () => {
    let authorData = [];
    return apiFetch( { path: `/wp/v2/users?per_page=-1` } )
    .then(
        ( result ) => {
            result != null && result.length > 0 && result.forEach((item) => {
                let filterauthorData = {};
                let includesVal = ['id', 'link', 'name', 'slug'];
                Object.keys(item).forEach(key => {
                    if (includesVal.includes(key)) {
                        filterauthorData[key] = item[key];
                    }
                });
                authorData = [...authorData, filterauthorData]
            })
            return authorData;
        },
        ( error ) => {
            console.log("error",error)
        }
    );
}

//Get Categories
export const getCategory = () => {
    let categoryData = [];
    return apiFetch( { path: `/wp/v2/categories?per_page=-1` } )
    .then(
        ( result ) => {
            result != null && result.length > 0 && result.forEach((item) => {
                let filterCategoryData = {};
                let includesVal = ['id', 'link', 'name', 'slug', 'parent'];
                Object.keys(item).forEach(key => {
                    if (includesVal.includes(key)) {
                        filterCategoryData[key] = item[key];
                    }
                });
                categoryData = [...categoryData, filterCategoryData]
            })
            return categoryData;
        },
        ( error ) => {
            console.log("error",error)
        }
    );
}

//Get Tags
export const getTag = () => {
    let tagsData = [];
    return apiFetch( { path: `/wp/v2/tags?per_page=-1` } )
    .then(
        ( result ) => {
            result != null && result.length > 0 && result.forEach((item) => {
                let filterTagsData = {};
                let includesVal = ['id', 'link', 'name', 'slug'];
                Object.keys(item).forEach(key => {
                    if (includesVal.includes(key)) {
                        filterTagsData[key] = item[key];
                    }
                });
                tagsData = [...tagsData, filterTagsData]
            })
            return tagsData;
        },
        ( error ) => {
            console.log("error",error)
        }
    );
}

//Get Posts for Include or Exclude
export const getLatest5Posts = () => {
    let tagsData = [];
    return apiFetch( { path: `/wp/v2/posts/?per_page=5` } )
    .then(
        ( result ) => {
            result != null && result.length > 0 && result.forEach((item) => {
                let filterTagsData = {};
                let includesVal = ['id', 'title'];
                Object.keys(item).forEach(key => {
                    if (includesVal.includes(key)) {
                        filterTagsData[key] = item[key];
                    }
                });
                tagsData = [...tagsData, filterTagsData]
            })
            return tagsData;
        },
        ( error ) => {
            console.log("error",error)
        }
    );
}

//Get Posts for Include or Exclude
export const getPostsBySearchString = (search) => {
    let postsData = [];
    return apiFetch( { path: `/wp/v2/search/?search=${search}&type=post` } )
    .then(
        ( result ) => {
            result != null && result.length > 0 && result.forEach((item) => {
                let filterPostsData = {};
                let includesVal = ['id', 'title'];
                Object.keys(item).forEach(key => {
                    if (includesVal.includes(key)) {
                        filterPostsData[key] = item[key];
                    }
                });
                postsData = [...postsData, filterPostsData]
            })
            return postsData;
        },
        ( error ) => {
            console.log("error",error)
        }
    );
}