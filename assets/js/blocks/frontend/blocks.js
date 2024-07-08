(() => {
    var CCBasedObject = {
        processPayment: ({ resolve, billing }) => {
            
            const desc = document.querySelector('#blheson_cc_description');
            desc.innerHTML = 'Processing Payment'
            setTimeout(() => {
                console.log("Resolving")
                resolve({ type: 'success', message: 'Complete' })
            }, 2000);
        },
        init: () => {
            const cc = document.querySelector('#blheson_cc_id');
            if (cc) {
                cc.innerHTML = '<style> .form_group { margin: 0 20px; } .form_group input { width: 100%; height: 50px; } .form_group input.short { width: 100%; height: 50px; } .form_group label { font-size: 10px; } .d-flex { display: flex; justify-content: space-between; }</style><div class="form_group"><label for="">CARD NUMBER</label><input type="text" placeholder="0000 0000 0000 0000"></div><div class="form_group"><label for="">CARD HOLDER</label><input type="text" placeholder="xxxxx xxxxx"></div><div class="d-flex"><div class="form_group">    <label for="">EXPIRY DATE</label>    <input type="text" placeholder="04/22"></div><div class="form_group ">    <label for="">CVV</label>    <input type="number" class="short" placeholder="000"></div></div>'
            }

        }

    }
    window.CCBasedObject = CCBasedObject
})();
(() => {
    "use strict";
    const { React, wc: { wcBlocksRegistry, wcSettings }, wp: { i18n, htmlEntities } } = window;

    const getTitle = ({ title }) => htmlEntities.decodeEntities(title) || (0, i18n.__)(
        "CC",
        "woo-blheson_cc"
    );

    const getDescription = ({ description }) => htmlEntities.decodeEntities(description || "");

    const getContent = (data) => {
        const { onPaymentSetup } = data.eventRegistration;
        React.useEffect(() => {
            window.CCBasedObject.init();
        }, [])
        React.useEffect(() => {
            const onPaymentSetupUnsubscribe = onPaymentSetup(async () => {
                const result = await new Promise(async (resolve, reject) => {
                    window.CCBasedObject.processPayment({ resolve, billing: data.billing });
                });
                return result
            });
            return onPaymentSetupUnsubscribe
        }, [onPaymentSetup])
        return React.createElement(
            'div', { className: 'form_group box_form', },
            React.createElement('div', { id: 'blheson_cc_description' }, htmlEntities.decodeEntities(data.description || "")), React.createElement('div', { id: 'blheson_cc_id' }))


    };

    const gatewayData = wcSettings.getSetting("blheson_cc_data", {});
    console.log({ gatewayData }, wcSettings.allSettings)
    // Get the translated title
    const title = getTitle({ title: gatewayData.title });

    // Define the payment method object
    const paymentMethod = {
        name: "blheson_cc",
        label: (0, React.createElement)((({ title }) => (0, React.createElement)(
            React.Fragment,
            null,
            (0, React.createElement)("div", {
                style: { display: "flex", flexDirection: "row", gap: "0.5rem" }
            },
                (0, React.createElement)("div", null, getTitle({ title })),

            ))),
            { title }),

        content: (0, React.createElement)(getContent, { description: gatewayData.description, environment: gatewayData.environment }),

        edit: React.createElement(getDescription, { description: gatewayData.description }),
        canMakePayment: (data) => { return true },
        ariaLabel: title,
        supports: {
            features: gatewayData.supports
        },
        placeOrderButtonLabel: 'Pay with CC'
    };

    wcBlocksRegistry.registerPaymentMethod(paymentMethod);
})();
