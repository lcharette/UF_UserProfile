# Custom Profile Fields Sprinkle


## Install
`cd` into the sprinkle directory of UserFrosting and clone as submodule:
```
git submodule add git@github.com:lcharette/UF_CustomProfileFields.git CustomProfileFields
```

### Install dependencies
This sprinkle requires the `FormGenerator` sprinkle. You'll find instruction on how to install it here : https://github.com/lcharette/UF_FormGenerator

### Add to the sprinkle list
Edit UserFrosting `app/sprinkles/sprinkles.json` file and add `CustomProfileFields` to the sprinkle list to enable it.

### Update the assets build & composer
From the UserFrosting `/build` folder, run `npm run uf-assets-install`
You may also need to run `composer update` from the `app/` folder.

### Install database migrations
Go to the `migrations/` directory and run `php install.php`.

# Licence
By [Louis Charette](https://github.com/lcharette). Copyright (c) 2016, free to use in personal and commercial software as per the MIT license.
