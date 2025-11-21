# Dockerfile for building Reddit Bot RPM on Amazon Linux 2023

FROM amazonlinux:2023

# Install build tools
RUN yum install -y \
    rpm-build \
    rpmdevtools \
    python3 \
    python3-pip \
    tar \
    gzip \
    && yum clean all

# Set up RPM build environment
RUN rpmdev-setuptree

# Set working directory
WORKDIR /build

# Build script will be run from outside
CMD ["/bin/bash"]

