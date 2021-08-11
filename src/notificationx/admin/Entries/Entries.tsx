import React, { useEffect, useState } from "react";
import nxHelper from "../../core/functions";
import withDocumentTitle from "../../core/withDocumentTitle";

const Entries = (props) => {
    const ID = parseInt(props?.match?.params?.id);
    const [entries, setEntries] = useState([]);

    useEffect(() => {
        nxHelper.get(`entries/${ID}`).then((res: any) => {
            setEntries(res);
        });
    }, [ID]);

    const list = entries.map((entry) => {
        const e = { ...entry, ...entry.data };
        delete e.data;
        return (
            <div>
                {Object.keys(e).map((key) => {
                    return (
                        <div>
                            <span>{key}</span>
                            <span>{e[key]}</span>
                        </div>
                    );
                })}
            </div>
        );
    });

    return (
        <div>
            <h1>Entries</h1>
            {list}
        </div>
    );
};

export default withDocumentTitle(Entries, "Entries");
