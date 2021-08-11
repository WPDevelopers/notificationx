import { GetTemplate } from "../../frontend/themes";

function AdvTmplDefault(settings) {
    const template = settings['notification-template'];
    const x = GetTemplate(settings)
    // console.log('adv template', settings, x);


}

export default AdvTmplDefault
