#!/usr/bin/env sh

VERSION=$1
if [ $# -eq 0 ]
then
  echo "Usage: $0 semver"
  echo "  Updates all files with a given version and pushes changes to both the"
  echo "  git repository and WordPress subversion one."
  echo "  Example: $0 1.0.5"
  exit 1
fi

echo "Stamping git repo with tag $VERSION..."
sed -i "s/Stable tag: .*/Stable tag: $VERSION/g" readme.txt
sed -i 's/  "version": ".*",/  "version": "'$VERSION'",/g' package.json
sed -i "s/const VERSION = '.*';/const VERSION = '$VERSION';/g" snappic-for-woocommerce.php
sed -i 's/"Project-Id-Version: Snappic for WooCommerce .*\\n"/"Project-Id-Version: Snappic for WooCommerce '$VERSION'\\n"/g' languages/snappic-for-woocommerce.pot
git add readme.txt package.json snappic-for-woocommerce.php languages/snappic-for-woocommerce.pot
git commit -m "Bumps version to $VERSION and tags."
git tag $VERSION
git push origin master --tags

echo "Removing previous SVN repository versions..."
rm -rf /tmp/snappic-woocommerce-svn
echo "Cloning SVN repo..."
svn co https://plugins.svn.wordpress.org/snappic /tmp/snappic-woocommerce-svn
echo "Replacing files with latest version..."
cp -R * /tmp/snappic-woocommerce-svn/trunk

cd /tmp/snappic-woocommerce-svn
echo "Commiting changes to SVN repo..."
svn ci --username snappic
svn cp trunk tags/$VERSION
