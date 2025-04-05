#!/bin/bash
set -e

USER_ID=${PUID:-1000}
GROUP_ID=${PGID:-1000}

echo "Starting with UID: $USER_ID, GID: $GROUP_ID"

# Create group if it doesn't exist
if ! getent group $GROUP_ID > /dev/null; then
    groupadd -g $GROUP_ID usergroup
fi

# Create user if it doesn't exist
if ! getent passwd $USER_ID > /dev/null; then
    useradd -u $USER_ID -g $GROUP_ID -m -s /bin/bash appuser
fi

# Always ensure proper ownership
chown -R $USER_ID:$GROUP_ID /var/www

# Execute the command
exec "$@"