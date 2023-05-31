import favloader from "./flashing/favloader";
import interval from "./flashing/webWorker";

let interval_id = null;
// @ts-ignore
const settings     = window.nx_flashing_tab;
const ogTitle      = window.document.title;
const initialDelay = 2000; // @todo get from settings
const delayBetween = 2000; // @todo get from settings


favloader.init({
    size: 16,
    radius: 6,
    thickness: 2,
    color: '#0F60A8',
    duration: 5000,
    // png: "https://nxm.test/wp-content/plugins/notificationx/assets/public/image/icons/verified.svg",
    // png: settings.ft_icon_1,
    // gif: "https://nxm.test/wp-content/plugins/notificationx/assets/public/image/icons/blue-face-non-looped.gif",
    // frame: (context: CanvasRenderingContext2D) => {
    //     // The size of the emoji is set with the font
    //     context.font = '14px serif'
    //     // use these alignment properties for "better" positioning
    //     context.textAlign = "center";
    //     context.textBaseline = "middle";
    //     context.fillText('😜', 8, 8)

    // },
});

window.addEventListener('visibilitychange', (event) => {
    if (document.visibilityState === "visible") {
        favloader.stop();
        clear();
    } else {
        // favloader.start();
        setTimeout(() => {
            interval_id = interval.set(changeTitle, delayBetween);
        }, initialDelay);
    }
});

const changeTitle = () => {
    if(document.title !== settings.ft_message_1){
        document.title = settings.ft_message_1;
        favloader.animatePng(settings.ft_icon_1)
    }
    else{
        document.title = settings.ft_message_2;
        favloader.animatePng(settings.ft_icon_2)
    }
}

const clear = () => {
    document.title = ogTitle;
    if (interval_id) {
        interval.clear(interval_id);
        interval_id = null;
    }
}
// window.addEventListener('blur', () => {
//     // Tab is out of focus
//     console.log('Tab is blurred');




// });
