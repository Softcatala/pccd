/*
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

(() => {
    // Hack: disable resize listener for performance reasons.
    const originalAddEventListener = window.addEventListener;
    window.addEventListener = function (event, listener, options) {
        if (event !== "resize") {
            originalAddEventListener.call(this, event, listener, options);
        }
    };

    // eslint-disable-next-line no-new, no-undef
    new simpleDatatables.DataTable("#fonts", {
        columns: [
            {
                select: 2,
                sort: "asc",
            },
        ],
        labels: {
            noResults: "No s'ha trobat cap resultat",
        },
        locale: "ca",
        paging: false,
        tableRender: (_data, table, type) => {
            if (type === "print") {
                return table;
            }
            const tHead = table.childNodes[0];
            const filterHeaders = {
                childNodes: tHead.childNodes[0].childNodes.map((th, index) => {
                    const buttonNode = th.childNodes.find((node) => node.nodeName === "BUTTON");
                    const labelText =
                        buttonNode && buttonNode.childNodes.length > 0 && buttonNode.childNodes[0].nodeName === "#text"
                            ? buttonNode.childNodes[0].data
                            : index + 1;
                    return {
                        childNodes: [
                            {
                                attributes: {
                                    "aria-label": `Cerca a la columna ${labelText}`,
                                    "class": "datatable-input",
                                    "data-and": true,
                                    "data-columns": `[${index}]`,
                                    "type": "search",
                                },
                                nodeName: "INPUT",
                            },
                        ],
                        nodeName: "TH",
                    };
                }),
                nodeName: "TR",
            };
            tHead.childNodes.push(filterHeaders);
            return table;
        },
    });
})();
