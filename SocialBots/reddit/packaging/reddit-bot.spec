Name:           reddit-bot
Version:        1.0.0
Release:        1%{?dist}
Summary:        YourCommentOnAShirt Reddit Bot

License:        MIT
URL:            https://yourcommentonashirt.com
Source0:        %{name}-%{version}.tar.gz

BuildArch:      noarch
Requires:       python3 >= 3.9
Requires:       python3-pip
Requires:       systemd

%description
Reddit bot that monitors mentions and generates custom t-shirt product links
for YourCommentOnAShirt.com.

%prep
%setup -q

%build
# Nothing to build - pure Python

%install
# Create directory structure
mkdir -p %{buildroot}/opt/reddit-bot
mkdir -p %{buildroot}/etc/systemd/system
mkdir -p %{buildroot}/var/log/reddit-bot

# Copy application files
cp -r src %{buildroot}/opt/reddit-bot/
cp -r tests %{buildroot}/opt/reddit-bot/
cp bot.py %{buildroot}/opt/reddit-bot/
cp Pipfile %{buildroot}/opt/reddit-bot/
cp config.yaml.example %{buildroot}/opt/reddit-bot/
cp pytest.ini %{buildroot}/opt/reddit-bot/
cp README.md %{buildroot}/opt/reddit-bot/

# Copy systemd service file
cp packaging/reddit-bot.service %{buildroot}/etc/systemd/system/

%pre
# Create reddit-bot user if it doesn't exist
getent group reddit-bot >/dev/null || groupadd -r reddit-bot
getent passwd reddit-bot >/dev/null || \
    useradd -r -g reddit-bot -d /opt/reddit-bot -s /sbin/nologin \
    -c "Reddit Bot Service User" reddit-bot
exit 0

%post
# Install Python dependencies using pipenv
cd /opt/reddit-bot
pip3 install --user pipenv >/dev/null 2>&1 || true
export PATH="$HOME/.local/bin:$PATH"
pipenv install --deploy >/dev/null 2>&1 || true

# Set ownership
chown -R reddit-bot:reddit-bot /opt/reddit-bot
chown -R reddit-bot:reddit-bot /var/log/reddit-bot

# Reload systemd
systemctl daemon-reload

echo ""
echo "Reddit Bot installed successfully!"
echo ""
echo "Next steps:"
echo "  1. Create /opt/reddit-bot/.env with Reddit API credentials"
echo "  2. Copy config.yaml.example to config.yaml and adjust settings"
echo "  3. Enable service: sudo systemctl enable reddit-bot"
echo "  4. Start service: sudo systemctl start reddit-bot"
echo "  5. Check status: sudo systemctl status reddit-bot"
echo "  6. View logs: sudo journalctl -u reddit-bot -f"
echo ""

%preun
# Stop and disable service before uninstall
if [ $1 -eq 0 ]; then
    systemctl stop reddit-bot >/dev/null 2>&1 || true
    systemctl disable reddit-bot >/dev/null 2>&1 || true
fi

%postun
# Reload systemd after uninstall
if [ $1 -eq 0 ]; then
    systemctl daemon-reload
fi

%files
%defattr(-,root,root,-)
/opt/reddit-bot/*
/etc/systemd/system/reddit-bot.service
%dir %attr(0755,reddit-bot,reddit-bot) /var/log/reddit-bot

%changelog
* Sat Nov 08 2025 Reddit Bot Team
- Initial RPM release

