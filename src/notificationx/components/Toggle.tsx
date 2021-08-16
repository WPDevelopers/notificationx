import React, { useState, useEffect } from 'react'

const Toggle = ({ id: nxId, name, value: checked = false, ...rest }) => {
    const [isEnabled, setIsEnabled] = useState(checked);

    useEffect(() => {
        setIsEnabled(checked);
    }, [checked])

    const handleChange = (event) => {
        let target = event.target ? event.target : event.currentTarget;
        if (rest.onChange) {
            rest.onChange(event, target.checked, rest).then(res => {
                setIsEnabled(target.checked);
            }).catch(err => {
            });
        }
        else{
            setIsEnabled(target.checked);
        }
    }

    const id = `${name}_${nxId}`;

    return (
        <div className="nx-admin-status">
            <input type="checkbox" name={name} id={id} onChange={handleChange} checked={isEnabled} />
            <label htmlFor={id}></label>
        </div>
    )
}

export default Toggle;