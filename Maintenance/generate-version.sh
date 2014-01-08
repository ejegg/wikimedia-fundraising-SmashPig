#!/bin/bash
#
# This script should be run manually before deploying to production.  When
# run with no arguments, it will create a file SmashPig/version.php,
# if you wish to store the output elsewhere, pass the file path as argument 1.
#
# Also, config.php must require the generated file.  If the version is
# not found at runtime, the string "unknown" will be used instead.

if [ "x$1" == "x" ]; then
	root_dir="$(dirname $0)/.."
	dest="$root_dir/version.php"
else
	dest="$1"
fi
head_rev=$(git rev-parse --verify HEAD)

cat > $dest <<EOF
<?php
\$smashpig_version = "$head_rev";
EOF
