import classNames from "classnames";
import React, { useCallback, useEffect, useMemo, useState } from "react";
import { ActionMeta, default as ReactSelect, components } from "react-select";
import { fetchCategories, findOptionLabelByValue } from "../helper/helper";
import { selectStyles } from "../helper/styles";
import apiFetch from '@wordpress/api-fetch';

// Prepare options with checkbox
const Option = (props) => {    
    const isAllSelected = props.selectProps.value.some((selected) => selected.value === 'all');
    return (
        <div
            className={classNames(
                "checkbox-select-menu-list-item",
                { "blur-item": isAllSelected && props.data.value !== 'all' }
            )}
        >
            <components.Option {...props}>
                <span>{props.label}</span>
            </components.Option>
        </div>
    );
};

// Helper functions
export const addAllOption = (options, page) => {
    if( page == 1 ) {
        return [{ label: 'All', value: 'all' }, ...Object.values(options || [])];
    }
    return [...Object.values(options || [])];
};

export const getOptionsFlatten = (options) => {
  const optionsArray = [];
  options.forEach((category) => {
    if (category.options) {
      optionsArray.push(...category.options);
    } else {
      optionsArray.push(category);
    }
  });
  return optionsArray;
};

const BetterSelect = (props) => {
    const { name, multiple, onChange } = props;
    const [displayedOptions, setDisplayedOptions] = useState([]); // Paginated options
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);
    const options = props?.value.map((item) => ({
        value: item,
        label: item == 'all' ? 'All' : item,
    }));

    const [optionSelected, setOptionSelected] = useState(options ?? []);

  // Function to handle selection and deselection
  const handleChange = (newValue, actionMeta: ActionMeta<any>) => {
    if (actionMeta.action === "select-option") {
      if (actionMeta.option.value === "all") {
        newValue = [{ label: 'All', value: 'all' }];
      } else {
        // Ensure 'All' is not part of the selection when other items are selected
        newValue = newValue.filter((item) => item.value !== "all");
      }
    } else if (actionMeta.action === "deselect-option") {
      if (actionMeta.option.value === "all") {
        newValue = [];
      } else {
        newValue = newValue.filter((item) => item.value !== "all");
      }
    }
    setOptionSelected(newValue);
  };

  // Remove an item from selection
  const removeItem = (item) => {
    const updatedItems = optionSelected.filter((i) => i !== item);
    handleChange(updatedItems, {
      action: "deselect-option",
      option: item,
    });
  };

  // Function to fetch categories from the server
  const fetchOptions = async (page) => {
    try {
      setLoading(true);
      const data = {
        page: page,
        limit : 10,
      }
      let response = await fetchCategories(data);
      response = addAllOption(response, page);
      response = getOptionsFlatten(response);
    //   @ts-ignore 
      setDisplayedOptions((prevOptions) => [...prevOptions, ...response]);
    } catch (error) {
      console.error("Failed to load options:", error);
    } finally {
      setLoading(false);
    }
  };

  // Fetch the first set of categories on mount
  useEffect(() => {
    fetchOptions(1); // Initial load with page 1
  }, []);

  // Handle scroll event to load more categories when reaching the bottom
  const loadMoreOptions = () => {
    const isAllSelected = optionSelected.some((selected) => selected.value === 'all');
    if (isAllSelected || loading) {
      return;
    }
    setPage((prevPage) => prevPage + 1);
  };

  // Fetch new categories when the page state changes (pagination)
  useEffect(() => {
    if (page > 1) {
      fetchOptions(page);
    }
  }, [page]);

    useEffect(() => {
        onChange({
        target: {
            type: "checkbox-select",
            name,
            value: optionSelected
            ?.filter(item => item && item.value) // Ensure item is valid and has a 'value'
            .map((item) => item.value),
            multiple,
        },
        });
    }, [optionSelected]);
    
  return (
    <>
      <div
        className={classNames(
          "wprf-control",
          "wprf-control-wrapper",
          "wprf-checkbox-select",
          `wprf-${props.name}-checkbox-select`,
          props.classes
        )}
      >
        <div className="wprf-control-label">
          <label htmlFor={`${props.id}`}>{props.label}</label>
          <div className="selected-options">
            <ul>
              { (optionSelected && optionSelected[0] !== null) &&
                optionSelected
                  .map((item, index) => (
                    <li key={index}>
                      {item.label}
                      <button onClick={() => removeItem(item)}>
                        <i className="wpsp-icon wpsp-close"></i>
                      </button>
                    </li>
                  ))}
            </ul>
          </div>
        </div>
        <div className="wprf-checkbox-select-wrap wprf-checked wprf-label-position-right">
          <span className={`d-inline-block ${loading ? 'wpsp-checkbox-async-loading' : ''}`}>
            <ReactSelect
              options={displayedOptions}
              styles={selectStyles}
              isMulti
              closeMenuOnSelect={false}
              hideSelectedOptions={false}
              components={{ Option }}
              onChange={handleChange}
              value={optionSelected}
              controlShouldRenderValue={false}
              className="checkbox-select"
              classNamePrefix="checkbox-select"
              onMenuScrollToBottom={loadMoreOptions}
            />
          </span>
        </div>
      </div>
    </>
  );
};

export default BetterSelect;
