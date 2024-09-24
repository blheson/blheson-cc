(() => {
    var CCBasedObject = {
        processPayment: ({ resolve, billing }) => {
            const desc = document.getElementById('blheson_cc_description');

            desc.innerHTML = 'Processing Payment';

            setTimeout(() => {
                console.log("Payment has been processed")
                resolve({ type: 'success', message: 'payment uncompleted' })
            }, 1000)
        },
        init: () => {
            const cc = document.getElementById('blheson_cc_id');
            if (cc) {
                cc.innerHTML = '<style> .form_group { margin: 0 20px; } .form_group input { width: 100%; height: 50px; } .form_group input.short { width: 100%; height: 50px; } .form_group label { font-size: 10px; } .d-flex { display: flex; justify-content: space-between; } </style> <div class="form_group"> <label for="">CARD NUMBER</label> <input type="text" placeholder="0000 0000 0000 0000"> </div> <div class="form_group"> <label for="">CARD HOLDER</label> <input type="text" placeholder="xxxxx xxxxx"> </div> <div class="d-flex"> <div class="form_group"> <label for="">EXPIRY DATE</label> <input type="text" placeholder="04/22"> </div> <div class="form_group "> <label for="">CVV</label> <input type="number" class="short" placeholder="000"> </div> </div>'
            }
        }
    }
    window.CCBasedObject = CCBasedObject
})();

(() => {
    'use strict';
    window;
    const { React, wc: { wcBlocksRegistry, wcSettings }, wp: { i18n, htmlEntities } } = window;

    const gatewayData = wcSettings.getSetting('blheson_cc_data', {});
    const title = htmlEntities.decodeEntities(gatewayData.title);
    const getContent = (data) => {
        console.log({ data })
        React.useEffect(() => {
            //create a ui
            window.CCBasedObject.init();
        }, [])
        React.useEffect(() => {
            const onPaymentSetupUnSubscription = data.eventRegistration.onPaymentSetup(async () => {
                const result = await new Promise((resolve) => {
                    window.CCBasedObject.processPayment({ resolve, billing: data.billing })
                })
                return result;
            });
            return onPaymentSetupUnSubscription;
        }, [data.eventRegistration.onPaymentSetup]);

        return React.createElement('div', { className: 'form_group' },
            React.createElement('div', { id: 'blheson_cc_description' }, data.description),
            React.createElement('div', { id: 'blheson_cc_id' })
        )

    }

    const paymentMethodOptions = {
        name: 'blheson_cc',
        label: title,
        content: React.createElement(getContent, { description: gatewayData.description }),
        edit: React.createElement(getContent, { description: gatewayData.description }),
        canMakePayment: () => {
            return true;
        },
        supports: {
            features: ['products']
        },
        ariaLabel: title,
    }
    wcBlocksRegistry.registerPaymentMethod(paymentMethodOptions)
})()