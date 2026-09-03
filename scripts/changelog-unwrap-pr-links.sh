#!/usr/bin/env bash

# Turns the "[#123]" PR references that `changelogger write --add-pr-num`
# appends to changelog.txt into Markdown links to the GitHub pull request.

# Exit on error and output commands.
set -ex

CURRENT_DIR=$(pwd)
OS_TYPE=$(uname -s)
# sed arguments are passed a bit differently on different operating systems.
if [[ "$OS_TYPE" == "Darwin" ]]; then
	sed -E -i '' "s/^.* \[#([0-9]+)\]$/&(https:\/\/github.com\/woocommerce\/sensei-certificates\/pull\/\\1)/" "$CURRENT_DIR/changelog.txt"
elif [[ "$OS_TYPE" == "Linux" ]]; then
	# You are on Linux
	sed -E -i'' "s/^.* \[#([0-9]+)\]$/&(https:\/\/github.com\/woocommerce\/sensei-certificates\/pull\/\\1)/" "$CURRENT_DIR/changelog.txt"
else
	echo "Unsupported operating system: $OS_TYPE" >&2
	exit 1
fi
