# mybb-promethus

A MyBB plugin to expose metrics to Prometheus.

## Supported metrics

This plugin currently reports the following set of metrics:

- Number of users awaiting activation
- Number of messages waiting in the mail queue
- Maximum number of concurrent online users
- Number of unread reports
- Total number of reports
- MyBB version code number
- Number of threads in all forums
- Number of unapproved threads in all forums
- Number of deleted threads in all forums
- Number of posts in all forums
- Number of unapproved threads in all forums
- Number of deleted posts in all forums
- Number of registered users
- ID of the last registered user

## Configuring the plugin

Before using this plugin you need to configure your web server to set a couple of environment variables:

- `PROMETHEUS_USER`: The username used to access Prometheus metrics. Defaults to `prometheus`
- `PROMETHEUS_PASSWORD`: The password used to access Prometheus metrics.

These two configuration settings must match in both the MyBB web server configuration.

## Configuring Prometheus

You must configure Prometheus to add a new scrape config. Below is an example scrape configuration to scrape metrics:

```yaml
scrape_configs:
  - job_name: 'mybb'
    metrics_path: '/misc.php'
    scrape_interval: '5s'
    basic_auth:
      username: 'prometheus'
      password: 'change_me-123'
    params:
      action: ['prometheus_metrics']
    static_configs:
      - targets:
        - 'mybb.dev'
```

Obviously, you should change the target to your actual IP address/hostname and the username and password to those set as per the above section [`Configuring the plugin`](#configuring-the-plugin).