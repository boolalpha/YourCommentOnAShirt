#!/bin/bash
# Build script for creating Reddit Bot RPM package

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
VERSION="1.0.0"
PACKAGE_NAME="reddit-bot"

echo "Building Reddit Bot RPM v${VERSION}..."
echo "Project root: ${PROJECT_ROOT}"

# Clean previous builds
echo "Cleaning previous builds..."
rm -rf "${PROJECT_ROOT}/dist"
mkdir -p "${PROJECT_ROOT}/dist"

# Create tarball
echo "Creating source tarball..."
cd "${PROJECT_ROOT}"
mkdir -p "/tmp/${PACKAGE_NAME}-${VERSION}"

# Copy files (exclude .git, .venv, etc.)
rsync -av \
    --exclude='.git' \
    --exclude='.venv' \
    --exclude='venv' \
    --exclude='__pycache__' \
    --exclude='*.pyc' \
    --exclude='.pytest_cache' \
    --exclude='htmlcov' \
    --exclude='.coverage' \
    --exclude='.env' \
    --exclude='Pipfile.lock' \
    --exclude='*.log' \
    --exclude='logs/' \
    --exclude='dist/' \
    --exclude='*.rpm' \
    --exclude='*.tar.gz' \
    src/ tests/ bot.py Pipfile config.yaml.example pytest.ini README.md packaging/ \
    "/tmp/${PACKAGE_NAME}-${VERSION}/"

cd /tmp
tar czf "${PACKAGE_NAME}-${VERSION}.tar.gz" "${PACKAGE_NAME}-${VERSION}"
mv "${PACKAGE_NAME}-${VERSION}.tar.gz" "${PROJECT_ROOT}/dist/"
rm -rf "/tmp/${PACKAGE_NAME}-${VERSION}"

echo "Tarball created: dist/${PACKAGE_NAME}-${VERSION}.tar.gz"

# Build RPM using Docker
echo "Building RPM in Docker container..."
cd "${PROJECT_ROOT}"

docker build -t reddit-bot-builder -f packaging/Dockerfile packaging/

docker run --rm \
    -v "${PROJECT_ROOT}:/workspace" \
    reddit-bot-builder \
    /bin/bash -c "
        set -e
        cd /workspace
        
        # Copy files to RPM build directories
        cp dist/${PACKAGE_NAME}-${VERSION}.tar.gz ~/rpmbuild/SOURCES/
        cp packaging/reddit-bot.spec ~/rpmbuild/SPECS/
        
        # Build RPM
        cd ~/rpmbuild/SPECS
        rpmbuild -ba reddit-bot.spec
        
        # Copy built RPMs to output
        cp ~/rpmbuild/RPMS/noarch/*.rpm /workspace/dist/
        cp ~/rpmbuild/SRPMS/*.rpm /workspace/dist/ 2>/dev/null || true
        
        # List built packages
        ls -lh /workspace/dist/
    "

echo ""
echo "✓ Build complete!"
echo ""
echo "RPM package: dist/${PACKAGE_NAME}-${VERSION}-1.*.noarch.rpm"
echo ""
echo "To install on Amazon Linux:"
echo "  scp dist/${PACKAGE_NAME}-*.rpm ec2-user@your-server:/tmp/"
echo "  ssh ec2-user@your-server"
echo "  sudo rpm -ivh /tmp/${PACKAGE_NAME}-*.rpm"
echo ""

