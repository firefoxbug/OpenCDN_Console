%define         VERSION 1.1
Name:           opencdn-console
Version:        %VERSION
Release:	2%{?dist}
Summary:	opencdn-console

Group:		tools
License:	GNU
URL:		http://www.ocdn.me
BuildRoot:	/tmp/billing-build-root
Requires:       httpd mysql-server php php-cli php-mbstring php-pdo php-mysql php-common bc
Requires:       inotify-tools MySQL-python syslog-ng 
BuildArch: noarch
#BuildRequires: gcc
#Requires:      gcc
Autoreq:	no
%define _builddir           .
%define _rpmdir             .
%define _srcrpmdir          .
%define _build_name_fmt     %%{NAME}-%%{VERSION}-%%{RELEASE}-%%{ARCH}.rpm
%define __os_install_post   /usr/lib/rpm/brp-compress; echo 'Not stripping.'

%description
opencdn-console

%prep
#%setup -q

%build
#make 


%install
rm -rf $RPM_BUILD_ROOT
install -p -d -m 0755 $RPM_BUILD_ROOT/usr/local/opencdn/conf/
install -p -d -m 0755 $RPM_BUILD_ROOT/usr/local/opencdn/nginx/
install -p -d -m 0755 $RPM_BUILD_ROOT/usr/local/opencdn/node_logs/
install -p -d -m 0755 $RPM_BUILD_ROOT/usr/local/opencdn/ocdn/
install -p -d -m 0755 $RPM_BUILD_ROOT/usr/local/opencdn/pipe/
install -p -d -m 0755 $RPM_BUILD_ROOT/usr/local/opencdn/sbin/
install -p -d -m 0755 $RPM_BUILD_ROOT/var/log/opencdn/
install -p -d -m 0755 $RPM_BUILD_ROOT/etc/init.d/
install -p -d -m 0755 $RPM_BUILD_ROOT/etc/httpd/conf.d/
install -p -d -m 0755 $RPM_BUILD_ROOT/tmp/

install -p -m 0755 opencdn.conf $RPM_BUILD_ROOT/usr/local/opencdn/conf/
install -p -m 0755 do_accesslog $RPM_BUILD_ROOT/usr/local/opencdn/sbin/
install -p -m 0755 icmp $RPM_BUILD_ROOT/usr/local/opencdn/sbin/
install -p -m 0755 read_info $RPM_BUILD_ROOT/usr/local/opencdn/sbin/
install -p -m 0755 rsync_send $RPM_BUILD_ROOT/usr/local/opencdn/sbin/
install -p -m 0755 syn_node $RPM_BUILD_ROOT/usr/local/opencdn/sbin/
install -p -m 0755 opencdn $RPM_BUILD_ROOT/etc/init.d/
install -p -m 0755 ocdn.conf $RPM_BUILD_ROOT/etc/httpd/conf.d/
install -p -m 0755 syslog-ng.conf $RPM_BUILD_ROOT/tmp/
cp -dpr ocdn/* $RPM_BUILD_ROOT/usr/local/opencdn/ocdn/
cp -dpr nginx/* $RPM_BUILD_ROOT/usr/local/opencdn/nginx/

PREFIX=$RPM_BUILD_ROOT make install
#make install DESTDIR=$RPM_BUILD_ROOT


%clean
rm -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
/usr/local/opencdn/conf/
/usr/local/opencdn/nginx/
/usr/local/opencdn/node_logs/
/usr/local/opencdn/ocdn/
/usr/local/opencdn/pipe/
/usr/local/opencdn/sbin/
/var/log/opencdn/
/etc/init.d/opencdn
/tmp/
%config(noreplace) /etc/httpd/conf.d/ocdn.conf
%config(noreplace) /usr/local/opencdn/conf/opencdn.conf
%config(noreplace) /usr/local/opencdn/ocdn/database.php
%config(noreplace) /usr/local/opencdn/nginx/*
%pre

if [ $1 -eq 1 ]; then
    getent group apache > /dev/null || groupadd -r apache
    getent passwd apache > /dev/null || \
        useradd -r -d apache -g apache \
        -s /sbin/nologin -c "apache web server" apache
    exit 0
fi
%post
chown -R apache:apache /usr/local/opencdn/ocdn/
chown -R apache:apache /usr/local/opencdn/nginx/
chown -R apache:apache /usr/local/opencdn/conf/opencdn.conf
chkconfig --add opencdn
service opencdn restart
%postun
echo "ocdn.me" >/etc/rsyncd.pwd
chmod 600 /etc/rsyncd.pwd
rm -f /etc/syslog-ng/syslog-ng.conf
mv /tmp/syslog-ng.conf /etc/syslog-ng/syslog-ng.conf
%changelog

