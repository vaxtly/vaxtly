<div
    x-data="{
        activeRequestId: null,
        expandedCollections: @js($expandedCollections),
        expandedFolders: @js($expandedFolders),
        get allExpanded() {
            const collectionEls = this.$root.querySelectorAll('[data-collection-id]');
            if (!collectionEls.length) return false;
            return Array.from(collectionEls).every(el => this.expandedCollections[el.dataset.collectionId]);
        },
        toggleAllCollections() {
            const collectionEls = this.$root.querySelectorAll('[data-collection-id]');
            const ids = Array.from(collectionEls).map(el => el.dataset.collectionId);
            if (this.allExpanded) {
                this.expandedCollections = {};
                this.expandedFolders = {};
            } else {
                ids.forEach(id => this.expandedCollections[id] = true);
            }
            this.persistExpanded();
        },
        persistExpanded() {
            $wire.persistExpandedState(
                Object.keys(this.expandedCollections).filter(k => this.expandedCollections[k]),
                Object.keys(this.expandedFolders).filter(k => this.expandedFolders[k])
            )
        },
    }"
    x-on:sidebar-expanded-sync.window="expandedCollections = $event.detail.collections; expandedFolders = $event.detail.folders"
    x-on:toggle-all-collections.window="toggleAllCollections()"
    x-on:switch-tab.window="if ($event.detail.type !== 'environment') activeRequestId = $event.detail.requestId || null"
    x-effect="$dispatch('collections-expanded-state', { allExpanded: allExpanded })"
>
    @include('components.sidebar.partials.collections-list')
</div>
