import React, {  useEffect, useMemo, useState } from "react";
import AsyncSelect from "react-select/async";
import parse from "html-react-parser";
import nxHelper from "../core/functions";
import { useBuilderContext, withLabel } from 'quickbuilder';

const BetterSelect = (props) => {
	const builderContext = useBuilderContext();
	let { id, name, multiple, placeholder, onChange, parentIndex } = props;

	const [options, setOptions] = useState(builderContext.eligibleOptions(props.option));
	const [sOption, setSOption] = useState(props?.values);
	const [isAjaxRunning, setIsAjaxRunning] = useState(false);
    const [updatedOptions, setUpdatedOptions] = useState(options);
	// const [lastRequest, setLastRequest] = useState("");
	const handleMenuOpen = (inputValue, callback) => {
	    // AJAX
        if (props.ajax && (!props.ajax.rules || when(props.ajax.rules, builderContext.values))) {
            if (!inputValue) {
                console.log('hello');
                console.log('options',options);

                callback(options);
                return;
            }
            if (inputValue.length < 3) {
                    callback([
                        {
                            label: "Please type 3 or more characters.",
                            value: null,
                            disabled: true,
                        }
                    ]);
                return;
            }

            let data = { inputValue };
            Object.keys(props.ajax.data).map((singleData) => {
                if (props.ajax.data[singleData].indexOf("@") > -1) {
                    let eligibleKey = props.ajax.data[singleData].substr(1);
                    data[singleData] = builderContext.values?.[eligibleKey];
                } else {
                    data[singleData] = props.ajax.data[singleData];
                }
            });
            if (!isAjaxRunning && inputValue) {

                setIsAjaxRunning(true);
                window.lastRequest = null;                
                return nxHelper.getData(data).then((response) => {
                        callback(response);
                        return response;
                    })
                    .finally(() => {
                        setIsAjaxRunning(false);

                        if (window.lastRequest) {
                            const lr = window.lastRequest;
                            window.lastRequest = null;
                            handleMenuOpen(...lr);
                        }

                        window.lastCompleteRequest = inputValue;
                    });
            } else {
                window.lastRequest = [inputValue, callback];
            }
        }
    };

    useEffect(() => {
        let selectedValues = Array.isArray(sOption) ? sOption.map(item => item.value) : [sOption?.value];
        const hasAllSelected = selectedValues.includes('all');

        // If "all" is selected along with others, remove others
        if (hasAllSelected && selectedValues.length > 1) {
            const onlyAll = Array.isArray(sOption)
                ? sOption.find(item => item.value === 'all')
                : sOption;

            setSOption(onlyAll ? [onlyAll] : []);
            return;
        }

        if (hasAllSelected) {
            const newOptions = Object.values(options).map(option => ({
                ...option,
                disabled: option.value !== 'all',
            }));
            setUpdatedOptions(newOptions);
        } else {
            const newOptions = Object.values(options).map(option => ({
                ...option,
                disabled: false,
            }));
            setUpdatedOptions(newOptions);
        }
    }, [options, sOption]);


	useEffect(() => {
		setOptions(builderContext.eligibleOptions(props.option));
	}, [builderContext.values.source]);

	useEffect(() => {
		onChange({
			target: {
				type: "select",
				name,
				value: sOption,
				multiple,
			},
		});        
	}, [sOption]);


	return (
		<div className="wprf-async-select-wrapper">
			<AsyncSelect
				cacheOptions
				loadOptions={handleMenuOpen}
				defaultOptions={Object.values(updatedOptions)}
				isDisabled={props?.disable}
                isMulti={multiple ?? false}
				classNamePrefix="wprf-async-select"
				// defaultMenuIsOpen={true}
				id={id}
				name={name}
				placeholder={placeholder}
				formatOptionLabel={(option, meta) => {
					if (meta?.inputValue?.length && option.name) {
						if (
							option.name
								.toLowerCase()
								.includes(meta?.inputValue?.toLowerCase())
						) {
							let x = option?.name;
							let regX = new RegExp(
								`(${meta?.inputValue})`,
								"gi"
							);
							let name = option.name?.replace(
								regX,
								"<strong style={font-weight: 900}>$1</strong>"
							);
							let address = option.address?.replace(
								regX,
								"<strong style={font-weight: 900}>$1</strong>"
							);
							return (
								<>
									{parse(name || "")}{" "}
                                    Test
									<small>{parse(address || "")}</small>
								</>
							);
						}
					}
					return (
						<>
							{option.name ? (
								<>
									<b>{option.name}</b>{" "}
								</>
							) : (
								<>{option.label}{" "}</>
							)}
							{option.address && <small>{option.address}</small>}
						</>
					);
				}}
				value={sOption}
				isClearable={true}
				isOptionDisabled={(option) => option?.disabled}
				onChange={(option) => setSOption(option)} // option or options
			/>
		</div>
	);
};

export default withLabel(BetterSelect);