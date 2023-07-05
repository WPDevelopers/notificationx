
type FlashingTab = {
    icon: string;
    image?: string;
    message: string
};

type FlashingThemeOne = {
    'icon-one': string,
    'icon-two': string,
}
type FlashingThemeFour = {
    'default'       : FlashingThemeOne,
    'is-show-empty': boolean,
    'alternative'  ?: FlashingThemeOne,
}
type FlashingIcon = {
    name       : string,
    iconPrefix? : string,
    value      : string,
    onChange   : Function,
    options    : Array<{icon: string, label: string}>,
    count     ?: string
}
